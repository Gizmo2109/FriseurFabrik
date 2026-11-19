<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FooterAbout implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="footer-col"><p class="site-footer__name">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</p><div class="site-footer__text">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</div></div>';
    }
}
