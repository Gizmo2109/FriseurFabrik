<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class GalleryItem implements _\ComponentInterface
{
    private function __construct(
        private Img $image,
        private string $largeSrc,
    ) {
    }

    public static function create(
        Img $image,
        string $largeSrc,
    ): self {
        return new self(
            image: $image,
            largeSrc: $largeSrc,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<a class="gallery__item" href="' . _\Util::escapeAttributeValue($this->largeSrc) . '" data-lightbox><img src="' . _\Util::escapeAttributeValue($this->image->src) . '"' . ((($temp = $this->image->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 900px) 33vw, 50vw" alt="' . _\Util::escapeAttributeValue($this->image->alt) . '"' . ((($temp = $this->image->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" /></a>';
    }
}
