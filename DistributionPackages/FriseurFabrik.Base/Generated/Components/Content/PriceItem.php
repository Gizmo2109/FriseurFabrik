<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class PriceItem implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $name,
        private ?_\ComponentInterface $description,
        private ?_\ComponentInterface $price,
        private ?_\ComponentInterface $duration,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $name,
        _\ComponentInterface|string|null $description,
        _\ComponentInterface|string|null $price,
        _\ComponentInterface|string|null $duration,
    ): self {
        return new self(
            name: is_string($name) ? _\StringComponent::fromString($name) : $name,
            description: is_string($description) ? _\StringComponent::fromString($description) : $description,
            price: is_string($price) ? _\StringComponent::fromString($price) : $price,
            duration: is_string($duration) ? _\StringComponent::fromString($duration) : $duration,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="price-item"><div class="price-item__main"><span class="price-item__name">' . ((($temp = $this->name) === null) ? '' : $temp->render()) . '</span><span class="price-item__dots" aria-hidden="true"></span><span class="price-item__price">' . ((($temp = $this->price) === null) ? '' : $temp->render()) . '</span></div><div class="price-item__meta"><span class="price-item__description">' . ((($temp = $this->description) === null) ? '' : $temp->render()) . '</span><span class="price-item__duration">' . ((($temp = $this->duration) === null) ? '' : $temp->render()) . '</span></div></div>';
    }
}
