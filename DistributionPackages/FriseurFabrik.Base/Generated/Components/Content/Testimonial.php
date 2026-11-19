<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Testimonial implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $quote,
        private ?_\ComponentInterface $author,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $quote,
        _\ComponentInterface|string|null $author,
    ): self {
        return new self(
            quote: is_string($quote) ? _\StringComponent::fromString($quote) : $quote,
            author: is_string($author) ? _\StringComponent::fromString($author) : $author,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<figure class="testimonial"><blockquote class="testimonial__quote">' . ((($temp = $this->quote) === null) ? '' : $temp->render()) . '</blockquote><figcaption class="testimonial__author">' . ((($temp = $this->author) === null) ? '' : $temp->render()) . '</figcaption></figure>';
    }
}
