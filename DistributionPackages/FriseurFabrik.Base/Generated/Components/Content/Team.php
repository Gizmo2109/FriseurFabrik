<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Team implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $intro,
        private ?_\ComponentInterface $members,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $intro,
        _\ComponentInterface|string|null $members,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            intro: is_string($intro) ? _\StringComponent::fromString($intro) : $intro,
            members: is_string($members) ? _\StringComponent::fromString($members) : $members,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="team"><header class="section-head"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="section-head__intro">' . ((($temp = $this->intro) === null) ? '' : $temp->render()) . '</div></header>' . ((($temp = $this->members) === null) ? '' : $temp->render()) . '</div>';
    }
}
