<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Headline implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $kicker,
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $intro,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $kicker,
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $intro,
    ): self {
        return new self(
            kicker: is_string($kicker) ? _\StringComponent::fromString($kicker) : $kicker,
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            intro: is_string($intro) ? _\StringComponent::fromString($intro) : $intro,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<header class="section-head"><p class="kicker">' . ((($temp = $this->kicker) === null) ? '' : $temp->render()) . '</p><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="section-head__intro">' . ((($temp = $this->intro) === null) ? '' : $temp->render()) . '</div></header>';
    }
}
