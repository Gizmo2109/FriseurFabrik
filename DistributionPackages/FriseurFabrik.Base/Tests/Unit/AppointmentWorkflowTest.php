<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Tests\Unit;

use FriseurFabrik\Base\Domain\Model\AppointmentRequest;
use FriseurFabrik\Base\Domain\Model\AppointmentStatus;
use FriseurFabrik\Base\Domain\Service\AppointmentInputValidator;
use FriseurFabrik\Base\Mail\Ics;
use FriseurFabrik\Base\Rendering\SalonData;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class AppointmentWorkflowTest extends UnitTestCase
{
    private function validate(array $body): array
    {
        // open Tuesday to Saturday, today is Thursday 2026-11-19
        $hours = ['hoursTuesday' => '9–18', 'hoursWednesday' => '9–18', 'hoursThursday' => '9–18', 'hoursFriday' => '9–18', 'hoursSaturday' => '9–14'];
        $salon = SalonData::fromProperties(static fn (string $name) => $hours[$name] ?? null);
        $today = new \DateTimeImmutable('2026-11-19 15:00', new \DateTimeZone('Europe/Berlin'));
        return AppointmentInputValidator::validate($body + ['name' => 'Lena Muster', 'phone' => '0931 123456', 'privacy' => '1'], $salon, $today);
    }

    #[Test]
    public function validInputIsTrimmedAndNormalized(): void
    {
        $result = $this->validate(['name' => '  Lena Muster ', 'email' => 'lena@example.org', 'date' => '2026-11-19', 'time' => 'abends', 'service' => '', 'message' => " Hallo \n"]);
        self::assertSame([], $result['errors']);
        self::assertSame('Lena Muster', $result['values']['name']);
        self::assertSame('2026-11-19', $result['values']['date']->format('Y-m-d'), 'today is allowed');
        self::assertNull($result['values']['service']);
        self::assertSame('Hallo', $result['values']['message']);
    }

    #[Test]
    public function invalidInputGetsAMessagePerField(): void
    {
        $result = $this->validate(['name' => 'L', 'phone' => 'call me', 'email' => 'no-mail', 'time' => 'nachts', 'privacy' => '']);
        self::assertSame(['name', 'phone', 'email', 'time', 'privacy'], array_keys($result['errors']));
        self::assertNull($result['values']['time']);

        self::assertSame('Der Wunschtermin liegt in der Vergangenheit.', $this->validate(['date' => '2026-11-18'])['errors']['date']);
        self::assertSame('An diesem Tag ist der Salon geschlossen.', $this->validate(['date' => '2026-11-23'])['errors']['date'], 'Monday');
        self::assertArrayHasKey('date', $this->validate(['date' => '2027-12-01'])['errors'], 'more than a year ahead');
        self::assertArrayHasKey('date', $this->validate(['date' => '23.11.2026'])['errors'], 'wrong format');
        self::assertArrayHasKey('phone', $this->validate(['phone' => '+-()/'])['errors'], 'needs digits');
    }

    private function request(): AppointmentRequest
    {
        return new AppointmentRequest('haarwerk', 'Lena', '0931 1', null, null, null, null, null, null);
    }

    #[Test]
    public function proposalCanBeAcceptedAndCompleted(): void
    {
        $request = $this->request();
        self::assertSame(AppointmentStatus::NEW, $request->getStatus());
        self::assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $request->getToken());

        $request->propose(new \DateTimeImmutable('2026-11-25 10:00'), 45, 'Maria');
        self::assertSame(AppointmentStatus::PROPOSED, $request->getStatus());
        $request->acceptProposal();
        self::assertSame(AppointmentStatus::CONFIRMED, $request->getStatus());
        self::assertSame('2026-11-25 10:45', $request->getAppointmentEnd()?->format('Y-m-d H:i'));
        $request->complete(false);
        self::assertSame(AppointmentStatus::NO_SHOW, $request->getStatus());

        $this->expectException(\DomainException::class);
        $request->confirm(new \DateTimeImmutable('2026-11-26 10:00'), 30, null);
    }

    #[Test]
    public function invalidTransitionsAreRejected(): void
    {
        foreach ([
            'accept without proposal' => fn (AppointmentRequest $r) => $r->acceptProposal(),
            'complete an open request' => fn (AppointmentRequest $r) => $r->complete(true),
            'cancel twice' => function (AppointmentRequest $r) { $r->cancelByCustomer(); $r->cancelByCustomer(); },
            'propose after decline' => function (AppointmentRequest $r) { $r->decline(); $r->propose(new \DateTimeImmutable(), 30, null); },
        ] as $case => $transition) {
            try {
                $transition($this->request());
                self::fail($case . ' must throw');
            } catch (\DomainException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function icsUsesUtcAndEscapesText(): void
    {
        $berlin = new \DateTimeZone('Europe/Berlin');
        $ics = Ics::event('uid-1@test', new \DateTimeImmutable('2026-11-25 10:00', $berlin), new \DateTimeImmutable('2026-11-25 10:45', $berlin), 'Schnitt, Farbe; Föhnen', 'Markt 1', "Zeile 1\nZeile 2");
        self::assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        self::assertStringContainsString("\r\nDTSTART:20261125T090000Z\r\nDTEND:20261125T094500Z\r\n", $ics);
        self::assertStringContainsString('SUMMARY:Schnitt\, Farbe\; Föhnen', $ics);
        self::assertStringContainsString('DESCRIPTION:Zeile 1\nZeile 2', $ics);
        self::assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }
}
