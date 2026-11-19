<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FactRow implements _\ComponentInterface
{
    private function __construct(
        private string $label,
        private string $value,
    ) {
    }

    public static function create(
        string $label,
        string $value,
    ): self {
        return new self(
            label: $label,
            value: $value,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="facts__row"><dt>' . _\Util::escapeText($this->label) . '</dt><dd>' . _\Util::escapeText($this->value) . '</dd></div>';
    }
}
