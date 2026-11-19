<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class ModuleTab implements _\ComponentInterface
{
    private function __construct(
        private string $label,
        private string $href,
        private string $count,
        private bool $active,
    ) {
    }

    public static function create(
        string $label,
        string $href,
        string $count,
        bool $active,
    ): self {
        return new self(
            label: $label,
            href: $href,
            count: $count,
            active: $active,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<a class="' . _\Util::joinAttributeValues('ff-tab', ($this->active ? 'is-active' : '')) . '" href="' . _\Util::escapeAttributeValue($this->href) . '">' . _\Util::escapeText($this->label) . (($this->count !== '') ? '<span class="ff-tab__count">' . _\Util::escapeText($this->count) . '</span>' : '') . '</a>';
    }
}
