<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Domain\Model;

enum AppointmentStatus: string
{
    case NEW = 'neu';
    case PROPOSED = 'vorgeschlagen';
    case CONFIRMED = 'bestaetigt';
    case DECLINED = 'abgesagt';
    case CANCELLED = 'storniert';
    case DONE = 'erledigt';
    case NO_SHOW = 'nicht-erschienen';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Neu',
            self::PROPOSED => 'Neuer Termin vorgeschlagen',
            self::CONFIRMED => 'Bestätigt',
            self::DECLINED => 'Abgesagt (Salon)',
            self::CANCELLED => 'Storniert (Kund:in)',
            self::DONE => 'Erledigt',
            self::NO_SHOW => 'Nicht erschienen',
        };
    }

    /** the salon still has to act / wait for an answer */
    public function isOpen(): bool
    {
        return $this === self::NEW || $this === self::PROPOSED;
    }

    /** the request is finished, nothing can be changed any more except notes */
    public function isClosed(): bool
    {
        return in_array($this, [self::DECLINED, self::CANCELLED, self::DONE, self::NO_SHOW], true);
    }
}
