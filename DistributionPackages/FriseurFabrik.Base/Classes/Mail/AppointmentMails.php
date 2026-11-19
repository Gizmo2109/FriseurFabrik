<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Mail;

use FriseurFabrik\Base\Components\Mail\Mail;
use FriseurFabrik\Base\Components\Mail\MailButton;
use FriseurFabrik\Base\Components\Mail\MailFact;
use FriseurFabrik\Base\Domain\Model\AppointmentRequest;
use FriseurFabrik\Base\Domain\Service\SalonSites;
use FriseurFabrik\Base\Rendering\HtmlDocument;
use FriseurFabrik\Base\Rendering\SalonData;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\SymfonyMailer\Service\MailerService;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\ComponentEngine\StringComponent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * All e-mails of the appointment workflow. Sending failures are logged and reported, they never break the workflow.
 */
#[Flow\Scope('singleton')]
class AppointmentMails
{
    #[Flow\Inject]
    protected MailerService $mailerService;

    #[Flow\Inject]
    protected SalonSites $salonSites;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    #[Flow\InjectConfiguration(path: 'mail.fromAddress')]
    protected string $fromAddress = 'termine@friseurfabrik.test';

    /** to the customer: request received */
    public function sendReceived(AppointmentRequest $request): ?bool
    {
        return $this->toCustomer($request, 'Ihre Terminanfrage bei %s', 'Vielen Dank für Ihre Anfrage!',
            '<p>Hallo ' . $this->e($request->getName()) . ',</p><p>wir haben Ihre Terminanfrage erhalten und melden uns schnellstmöglich, um den Termin zu bestätigen. Erst mit unserer Bestätigung ist der Termin fest gebucht.</p>',
            $this->requestFacts($request), '', [['Anfrage zurückziehen', $this->responseUri($request), '#888888']]);
    }

    /** to the salon: a new request arrived */
    public function sendSalonNotification(AppointmentRequest $request): ?bool
    {
        [$site, $salon] = $this->site($request);
        $to = $salon->notificationEmail ?? $salon->email;
        if ($to === null || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $facts = array_merge([['Name', $request->getName()], ['Telefon', $request->getPhone()]], $request->getEmail() ? [['E-Mail', $request->getEmail()]] : [], $this->requestFacts($request));
        $note = $request->getMessage() ? '<p><strong>Nachricht:</strong><br>' . nl2br($this->e($request->getMessage())) . '</p>' : '';
        return $this->send($salon, $to, $salon->name, $request->getEmail(), 'Neue Terminanfrage: ' . $request->getName(), 'Neue Terminanfrage über die Website',
            '<p>Über die Website ist eine neue Terminanfrage eingegangen. Bitte bestätigen Sie den Termin, schlagen Sie eine andere Zeit vor oder sagen Sie ab.</p>',
            $facts, $note, [['Im Backend öffnen', $this->moduleUri($request), $salon->accent()]]);
    }

    public function sendConfirmed(AppointmentRequest $request, string $message): ?bool
    {
        [, $salon] = $this->site($request);
        $start = $request->getAppointmentStart() ?? throw new \LogicException('No appointment start', 1794300010);
        $ics = Ics::event(
            $request->getToken() . '@friseurfabrik',
            $start,
            $request->getAppointmentEnd() ?? $start,
            'Friseurtermin bei ' . $salon->name,
            trim(($salon->street ?? '') . ', ' . ($salon->zipCity() ?? ''), ', '),
            trim(($request->getService() ?? 'Friseurtermin') . ($request->getAssignedStylist() ? ' bei ' . $request->getAssignedStylist() : '')),
        );
        return $this->toCustomer($request, 'Ihr Termin am ' . self::date($start) . ' ist bestätigt', 'Ihr Termin ist bestätigt',
            '<p>Hallo ' . $this->e($request->getName()) . ',</p><p>wir freuen uns auf Sie! Hier sind die Details zu Ihrem Termin. Die angehängte Kalenderdatei können Sie direkt in Ihren Kalender übernehmen.</p>',
            $this->appointmentFacts($request, $salon), $this->messageBlock($message),
            [['Termin absagen', $this->responseUri($request), '#888888']], ['termin.ics' => $ics]);
    }

    public function sendProposed(AppointmentRequest $request, string $message): ?bool
    {
        [, $salon] = $this->site($request);
        return $this->toCustomer($request, 'Terminvorschlag von %s', 'Wir schlagen Ihnen einen Termin vor',
            '<p>Hallo ' . $this->e($request->getName()) . ',</p><p>zu Ihrem Wunschtermin können wir Ihnen leider nichts anbieten – aber gerne diesen Termin. Bitte sagen Sie uns kurz, ob er Ihnen passt.</p>',
            $this->appointmentFacts($request, $salon), $this->messageBlock($message),
            [['Termin annehmen oder ablehnen', $this->responseUri($request), $salon->accent()]]);
    }

    public function sendDeclined(AppointmentRequest $request, string $message): ?bool
    {
        [, $salon] = $this->site($request);
        return $this->toCustomer($request, 'Ihre Terminanfrage bei %s', 'Leider können wir Ihren Termin nicht anbieten',
            '<p>Hallo ' . $this->e($request->getName()) . ',</p><p>leider können wir Ihre Terminanfrage nicht bestätigen. Rufen Sie uns gerne an' . ($salon->phone ? ' (' . $this->e($salon->phone) . ')' : '') . ' – wir finden bestimmt eine andere Möglichkeit.</p>',
            $this->requestFacts($request), $this->messageBlock($message), []);
    }

    public function sendMessage(AppointmentRequest $request, string $subject, string $message): ?bool
    {
        return $this->toCustomer($request, $subject, $subject, '<p>Hallo ' . $this->e($request->getName()) . ',</p>', [], $this->messageBlock($message),
            [['Zu Ihrer Anfrage', $this->responseUri($request), '#888888']]);
    }

    /** to the salon: the customer answered (accepted the proposal or cancelled) */
    public function sendCustomerAnswer(AppointmentRequest $request, bool $accepted): ?bool
    {
        [, $salon] = $this->site($request);
        $to = $salon->notificationEmail ?? $salon->email;
        if ($to === null || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $heading = $accepted ? $request->getName() . ' hat den Terminvorschlag angenommen' : $request->getName() . ' hat den Termin abgesagt';
        $facts = array_merge([['Name', $request->getName()], ['Telefon', $request->getPhone()]], $request->getAppointmentStart() ? $this->appointmentFacts($request, $salon) : $this->requestFacts($request));
        return $this->send($salon, $to, $salon->name, $request->getEmail(), $heading, $heading, '', $facts, '',
            [['Im Backend öffnen', $this->moduleUri($request), $salon->accent()]]);
    }

    public function responseUri(AppointmentRequest $request): string
    {
        [$site] = $this->site($request);
        return $this->salonSites->baseUri($site) . '/termin/antwort?token=' . $request->getToken();
    }

    public static function date(\DateTimeImmutable $date): string
    {
        return (new \IntlDateFormatter('de_DE', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, 'Europe/Berlin'))->format($date) ?: $date->format('d.m.Y');
    }

    public static function dateTime(\DateTimeImmutable $date): string
    {
        return self::date($date) . ', ' . $date->setTimezone(new \DateTimeZone('Europe/Berlin'))->format('H:i') . ' Uhr';
    }

    /** @return list<array{0: string, 1: string}> */
    private function requestFacts(AppointmentRequest $request): array
    {
        $wish = trim(($request->getPreferredDate() ? self::date($request->getPreferredDate()) : '') . ' ' . ($request->getPreferredTime() ?? ''));
        return array_values(array_filter([
            ['Leistung', $request->getService() ?? 'Beratung'],
            $wish !== '' ? ['Wunschtermin', $wish] : null,
            $request->getStylist() ? ['Wunsch-Stylist:in', $request->getStylist()] : null,
        ]));
    }

    /** @return list<array{0: string, 1: string}> */
    private function appointmentFacts(AppointmentRequest $request, SalonData $salon): array
    {
        $start = $request->getAppointmentStart();
        return array_values(array_filter([
            $start ? ['Termin', self::dateTime($start)] : null,
            $request->getDurationMinutes() ? ['Dauer', 'ca. ' . $request->getDurationMinutes() . ' Minuten'] : null,
            ['Leistung', $request->getService() ?? 'Beratung'],
            $request->getAssignedStylist() ? ['Bei', $request->getAssignedStylist()] : null,
            ['Adresse', trim(($salon->street ?? '') . ', ' . ($salon->zipCity() ?? ''), ', ')],
        ]));
    }

    private function messageBlock(string $message): string
    {
        return trim($message) !== '' ? '<p style="margin-top:16px;padding:12px 16px;background:#f6f3ef;border-radius:6px;">' . nl2br($this->e(trim($message))) . '</p>' : '';
    }

    /**
     * @param list<array{0: string, 1: string}> $facts
     * @param list<array{0: string, 1: string, 2: string}> $buttons label, href, color
     * @param array<string,string> $attachments filename => content
     */
    private function toCustomer(AppointmentRequest $request, string $subject, string $heading, string $intro, array $facts, string $note, array $buttons, array $attachments = []): ?bool
    {
        if ($request->getEmail() === null) {
            return null;
        }
        [, $salon] = $this->site($request);
        return $this->send($salon, $request->getEmail(), $request->getName(), $salon->email, sprintf($subject, $salon->name), $heading, $intro, $facts, $note, $buttons, $attachments);
    }

    /**
     * @param list<array{0: string, 1: string}> $facts
     * @param list<array{0: string, 1: string, 2: string}> $buttons
     * @param array<string,string> $attachments
     * @return bool whether the mail was handed over to the mail server
     */
    private function send(SalonData $salon, string $to, string $toName, ?string $replyTo, string $subject, string $heading, string $intro, array $facts, string $note, array $buttons, array $attachments = []): bool
    {
        $footer = implode(' · ', array_filter([$salon->name, $salon->street, $salon->zipCity(), $salon->phone ? 'Tel. ' . $salon->phone : null]));
        $html = new HtmlDocument(Mail::create(
            salonName: $salon->name,
            accent: $salon->accent(),
            preheader: $heading,
            heading: $heading,
            intro: StringComponent::fromHtmlString($intro),
            facts: ComponentList::list(...array_map(static fn (array $fact) => MailFact::create(label: $fact[0], value: $fact[1]), $facts)),
            note: StringComponent::fromHtmlString($note),
            buttons: ComponentList::list(...array_map(static fn (array $button) => MailButton::create(label: $button[0], href: $button[1], color: $button[2]), $buttons)),
            footer: $footer,
        ));
        $text = trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>'], ["\n\n", "\n"], $intro . "\n" . $note)), ENT_QUOTES | ENT_HTML5))
            . "\n\n" . implode("\n", array_map(static fn (array $fact) => $fact[0] . ': ' . $fact[1], $facts))
            . "\n\n" . implode("\n", array_map(static fn (array $button) => $button[0] . ': ' . $button[1], $buttons))
            . "\n\n-- \n" . $footer . "\n";

        $email = (new Email())
            ->from(new Address($this->fromAddress, $salon->name))
            ->to(new Address($to, $toName))
            ->subject($subject)
            ->html($html->render())
            ->text($text);
        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $email->replyTo($replyTo);
        }
        foreach ($attachments as $filename => $content) {
            $email->attach($content, $filename, 'text/calendar; charset=utf-8; method=PUBLISH');
        }
        try {
            $this->mailerService->getMailer()->send($email);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Appointment mail could not be sent: ' . $e->getMessage());
            return false;
        }
    }

    /** @return array{0: \Neos\Neos\Domain\Model\Site, 1: SalonData} */
    private function site(AppointmentRequest $request): array
    {
        $site = $this->salonSites->site($request->getSiteNodeName()) ?? throw new \RuntimeException('Unknown site ' . $request->getSiteNodeName(), 1794300011);
        return [$site, $this->salonSites->salonData($site)];
    }

    private function moduleUri(AppointmentRequest $request): string
    {
        [$site] = $this->site($request);
        return $this->salonSites->baseUri($site) . '/neos/management/termine?' . http_build_query(['moduleArguments' => [
            '@action' => 'show',
            'appointment' => $this->persistenceManager->getIdentifierByObject($request),
        ]]);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
    }
}
