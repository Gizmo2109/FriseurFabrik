<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FooterLinks implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $links,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $links,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            links: is_string($links) ? _\StringComponent::fromString($links) : $links,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="footer-col"><p class="site-footer__heading">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</p>' . ((($temp = $this->links) === null) ? '' : $temp->render()) . '</div>';
    }
}
