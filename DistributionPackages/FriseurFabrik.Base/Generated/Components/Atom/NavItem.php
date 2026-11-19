<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class NavItem implements _\ComponentInterface
{
    private function __construct(
        private string $label,
        private string $href,
        private bool $active,
        private bool $external,
    ) {
    }

    public static function create(
        string $label,
        string $href,
        bool $active,
        bool $external,
    ): self {
        return new self(
            label: $label,
            href: $href,
            active: $active,
            external: $external,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<li class="nav__item"><a class="' . _\Util::joinAttributeValues('nav__link', ($this->active ? 'is-active' : '')) . '" href="' . _\Util::escapeAttributeValue($this->href) . '"' . ($this->active ? ' aria-current="page"' : '') . ($this->external ? ' target="_blank"' : '') . ($this->external ? ' rel="noopener"' : '') . '>' . _\Util::escapeText($this->label) . '</a></li>';
    }
}
