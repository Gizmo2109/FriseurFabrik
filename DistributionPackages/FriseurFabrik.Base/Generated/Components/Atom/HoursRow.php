<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class HoursRow implements _\ComponentInterface
{
    private function __construct(
        private string $day,
        private string $time,
        private bool $closed,
        private bool $today,
    ) {
    }

    public static function create(
        string $day,
        string $time,
        bool $closed,
        bool $today,
    ): self {
        return new self(
            day: $day,
            time: $time,
            closed: $closed,
            today: $today,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="' . _\Util::joinAttributeValues('hours__row', ($this->closed ? 'is-closed' : ''), ($this->today ? 'is-today' : '')) . '"><dt>' . _\Util::escapeText($this->day) . '</dt><dd>' . _\Util::escapeText($this->time) . '</dd></div>';
    }
}
