<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class InstagramTile implements _\ComponentInterface
{
    private function __construct(
        private string $src,
        private string $alt,
        private ?string $href,
        private bool $video,
        private Icon $_11_40_Icon,
    ) {
    }

    public static function create(
        string $src,
        string $alt,
        ?string $href,
        bool $video,
    ): self {
        return new self(
            src: $src,
            alt: $alt,
            href: $href,
            video: $video,
            _11_40_Icon: Icon::create(
                name: IconName::PLAY,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<a class="insta__tile"' . ((($temp = $this->href) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"') . ' target="_blank" rel="noopener"><img src="' . _\Util::escapeAttributeValue($this->src) . '" alt="' . _\Util::escapeAttributeValue($this->alt) . '" loading="lazy" />' . ($this->video ? '<span class="insta__badge">' . $this->_11_40_Icon->render() . '</span>' : '') . '</a>';
    }
}
