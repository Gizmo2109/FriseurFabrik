<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class SlideItem implements _\ComponentInterface
{
    private function __construct(
        private Img $image,
        private string $caption,
    ) {
    }

    public static function create(
        Img $image,
        string $caption,
    ): self {
        return new self(
            image: $image,
            caption: $caption,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<figure class="slider__slide"><img src="' . _\Util::escapeAttributeValue($this->image->src) . '"' . ((($temp = $this->image->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 1200px) 1100px, 100vw" alt="' . _\Util::escapeAttributeValue($this->image->alt) . '"' . ((($temp = $this->image->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" /><figcaption>' . _\Util::escapeText($this->caption) . '</figcaption></figure>';
    }
}
