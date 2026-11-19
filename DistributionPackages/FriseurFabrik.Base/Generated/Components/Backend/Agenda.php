<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\AgendaDay;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Agenda implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<AgendaDay> $days
     */
    private function __construct(
        private _\ComponentListInterface $days,
        private string $rangeLabel,
    ) {
    }

    /**
     * @param _\ComponentListInterface<AgendaDay> $days
     */
    public static function create(
        _\ComponentListInterface $days,
        string $rangeLabel,
    ): self {
        return new self(
            days: $days,
            rangeLabel: $rangeLabel,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="ff-agenda"><p class="ff-muted">Bestätigte Termine ' . _\Util::escapeText($this->rangeLabel) . '</p>' . ((count($this->days) > 0) ? $this->days->render() : '<p class="ff-empty">In diesem Zeitraum sind keine Termine bestätigt.</p>') . '</div>';
    }
}
