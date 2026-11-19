<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Figure implements _\ComponentInterface
{
    private function __construct(
        private ?Img $image,
        private ?_\ComponentInterface $caption,
    ) {
    }

    public static function create(
        ?Img $image,
        _\ComponentInterface|string|null $caption,
    ): self {
        return new self(
            image: $image,
            caption: is_string($caption) ? _\StringComponent::fromString($caption) : $caption,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<figure class="figure">' . (($this->image !== null) ? '<img' . (($this->image?->src) !== null ? (((($temp = $this->image?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->image?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 1200px) 1200px, 100vw"' . (($this->image?->alt) !== null ? (((($temp = $this->image?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->image?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" />' : '<div class="media-placeholder"></div>') . '<figcaption>' . ((($temp = $this->caption) === null) ? '' : $temp->render()) . '</figcaption></figure>';
    }
}
