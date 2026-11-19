<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class SiteFooter implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $columns,
        private ?_\ComponentInterface $legal,
        private ?_\ComponentInterface $copyright,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $columns,
        _\ComponentInterface|string|null $legal,
        _\ComponentInterface|string|null $copyright,
    ): self {
        return new self(
            columns: is_string($columns) ? _\StringComponent::fromString($columns) : $columns,
            legal: is_string($legal) ? _\StringComponent::fromString($legal) : $legal,
            copyright: is_string($copyright) ? _\StringComponent::fromString($copyright) : $copyright,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<footer class="site-footer"><div class="container">' . ((($temp = $this->columns) === null) ? '' : $temp->render()) . '</div><div class="container site-footer__bottom"><p class="site-footer__copyright">' . ((($temp = $this->copyright) === null) ? '' : $temp->render()) . '</p>' . ((($temp = $this->legal) === null) ? '' : $temp->render()) . '</div></footer>';
    }
}
