<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Data;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Img
{
    private function __construct(
        public string $src,
        public string $alt,
        public ?string $srcset,
        public ?int $width,
        public ?int $height,
    ) {
    }

    public static function create(
        string $src,
        string $alt,
        ?string $srcset,
        ?int $width,
        ?int $height,
    ): self {
        return new self(
            src: $src,
            alt: $alt,
            srcset: $srcset,
            width: $width,
            height: $height,
        );
    }
}
