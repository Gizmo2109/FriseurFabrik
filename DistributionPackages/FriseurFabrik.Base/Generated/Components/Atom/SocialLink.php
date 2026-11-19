<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class SocialLink implements _\ComponentInterface
{
    private function __construct(
        private string $label,
        private string $href,
        private Icon $_9_4_Icon,
    ) {
    }

    public static function create(
        IconName $icon,
        string $label,
        string $href,
    ): self {
        return new self(
            label: $label,
            href: $href,
            _9_4_Icon: Icon::create(
                name: $icon,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<a class="social-link" href="' . _\Util::escapeAttributeValue($this->href) . '" target="_blank" rel="noopener" aria-label="' . _\Util::escapeAttributeValue($this->label) . '" title="' . _\Util::escapeAttributeValue($this->label) . '">' . $this->_9_4_Icon->render() . '</a>';
    }
}
