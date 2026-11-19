<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Faq implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $items,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $items,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            items: is_string($items) ? _\StringComponent::fromString($items) : $items,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="faq"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2>' . ((($temp = $this->items) === null) ? '' : $temp->render()) . '</div>';
    }
}
