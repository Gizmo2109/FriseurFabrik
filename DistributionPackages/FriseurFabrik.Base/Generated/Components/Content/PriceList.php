<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class PriceList implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $note,
        private ?_\ComponentInterface $items,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $note,
        _\ComponentInterface|string|null $items,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            note: is_string($note) ? _\StringComponent::fromString($note) : $note,
            items: is_string($items) ? _\StringComponent::fromString($items) : $items,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="price-list"><h3 class="price-list__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h3>' . ((($temp = $this->items) === null) ? '' : $temp->render()) . '<p class="price-list__note">' . ((($temp = $this->note) === null) ? '' : $temp->render()) . '</p></div>';
    }
}
