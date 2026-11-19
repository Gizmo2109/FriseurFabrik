<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Mail;

use Neos\Flow\Annotations as Flow;

/** Minimal iCalendar file for a confirmed appointment (RFC 5545) */
#[Flow\Proxy(false)]
final class Ics
{
    public static function event(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end, string $summary, string $location, string $description): string
    {
        $utc = static fn (\DateTimeImmutable $date) => $date->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $escape = static fn (string $value) => str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $value);
        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//FriseurFabrik//Termine//DE',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $utc(new \DateTimeImmutable()),
            'DTSTART:' . $utc($start),
            'DTEND:' . $utc($end),
            'SUMMARY:' . $escape($summary),
            'LOCATION:' . $escape($location),
            'DESCRIPTION:' . $escape($description),
            'END:VEVENT',
            'END:VCALENDAR',
        ]) . "\r\n";
    }
}
