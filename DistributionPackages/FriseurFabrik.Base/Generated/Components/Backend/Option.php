<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Option implements _\ComponentInterface
{
    private function __construct(
        private string $value,
        private string $label,
        private bool $selected,
    ) {
    }

    public static function create(
        string $value,
        string $label,
        bool $selected,
    ): self {
        return new self(
            value: $value,
            label: $label,
            selected: $selected,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<option value="' . _\Util::escapeAttributeValue($this->value) . '"' . ($this->selected ? ' selected' : '') . '>' . _\Util::escapeText($this->label) . '</option>';
    }
}
