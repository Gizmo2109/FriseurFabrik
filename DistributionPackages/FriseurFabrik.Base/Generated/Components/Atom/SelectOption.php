<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class SelectOption implements _\ComponentInterface
{
    private function __construct(
        private string $value,
        private string $label,
    ) {
    }

    public static function create(
        string $value,
        string $label,
    ): self {
        return new self(
            value: $value,
            label: $label,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<option value="' . _\Util::escapeAttributeValue($this->value) . '">' . _\Util::escapeText($this->label) . '</option>';
    }
}
