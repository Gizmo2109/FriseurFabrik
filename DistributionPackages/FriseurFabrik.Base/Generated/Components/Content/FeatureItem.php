<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FeatureItem implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
        private Icon $_9_32_Icon,
    ) {
    }

    public static function create(
        IconName $icon,
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            _9_32_Icon: Icon::create(
                name: $icon,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="feature"><span class="feature__icon">' . $this->_9_32_Icon->render() . '</span><h3 class="feature__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h3><p class="feature__text">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</p></div>';
    }
}
