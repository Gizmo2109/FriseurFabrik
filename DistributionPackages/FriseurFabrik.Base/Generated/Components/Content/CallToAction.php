<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class CallToAction implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
        private string $buttonLabel,
        private string $buttonHref,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
        string $buttonLabel,
        string $buttonHref,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            buttonLabel: $buttonLabel,
            buttonHref: $buttonHref,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="cta"><div class="cta__body"><h2 class="cta__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><p class="cta__text">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</p></div><a class="btn btn--primary btn--large" href="' . _\Util::escapeAttributeValue($this->buttonHref) . '">' . _\Util::escapeText($this->buttonLabel) . '</a></div>';
    }
}
