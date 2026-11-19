<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Domain\Service;

use FriseurFabrik\Base\Rendering\SalonData;
use Neos\Flow\Annotations as Flow;

/**
 * Validates the public appointment form. Returns normalized values and German error messages per field.
 */
#[Flow\Proxy(false)]
final class AppointmentInputValidator
{
    public const TIMES = ['vormittags', 'mittags', 'nachmittags', 'abends'];

    /**
     * @param array<string,mixed> $body raw POST data
     * @return array{values: array<string,mixed>, errors: array<string,string>}
     */
    public static function validate(array $body, SalonData $salon, \DateTimeImmutable $today): array
    {
        $input = static fn (string $key, int $maxLength = 255): ?string => is_string($body[$key] ?? null) && trim($body[$key]) !== ''
            ? mb_substr(trim($body[$key]), 0, $maxLength)
            : null;
        $errors = [];

        $name = $input('name', 120);
        if ($name === null || mb_strlen($name) < 2) {
            $errors['name'] = 'Bitte geben Sie Ihren Namen an.';
        }
        $phone = $input('phone', 40);
        if ($phone === null || !preg_match('/^[0-9+()\/\s.-]{5,40}$/', $phone) || strlen(preg_replace('/\D/', '', $phone) ?? '') < 5) {
            $errors['phone'] = 'Bitte geben Sie eine gültige Telefonnummer an.';
        }
        $email = $input('email', 160);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Diese E-Mail-Adresse ist ungültig.';
        }
        $date = null;
        if (($rawDate = $input('date', 10)) !== null) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $rawDate, $today->getTimezone()) ?: null;
            if ($date === null) {
                $errors['date'] = 'Bitte wählen Sie ein gültiges Datum.';
            } elseif ($date < $today->setTime(0, 0)) {
                $errors['date'] = 'Der Wunschtermin liegt in der Vergangenheit.';
            } elseif ($date > $today->modify('+1 year')) {
                $errors['date'] = 'Bitte wählen Sie einen Termin innerhalb der nächsten 12 Monate.';
            } elseif ($salon->hasHours() && $salon->hours[$date->format('l')] === null) {
                $errors['date'] = 'An diesem Tag ist der Salon geschlossen.';
            }
        }
        $time = $input('time', 20);
        if ($time !== null && !in_array($time, self::TIMES, true)) {
            $errors['time'] = 'Bitte wählen Sie eine Tageszeit aus der Liste.';
            $time = null;
        }
        if ($input('privacy') !== '1') {
            $errors['privacy'] = 'Bitte stimmen Sie der Verarbeitung Ihrer Angaben zu.';
        }

        return [
            'values' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'service' => $input('service', 200),
                'stylist' => $input('stylist', 120),
                'date' => $date,
                'time' => $time,
                'message' => $input('message', 2000),
            ],
            'errors' => $errors,
        ];
    }
}
