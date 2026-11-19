<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class TextImage implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $kicker,
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
        private ?Img $image,
        private bool $imageLeft,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $kicker,
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
        ?Img $image,
        bool $imageLeft,
    ): self {
        return new self(
            kicker: is_string($kicker) ? _\StringComponent::fromString($kicker) : $kicker,
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            image: $image,
            imageLeft: $imageLeft,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="' . _\Util::joinAttributeValues('text-image', ($this->imageLeft ? 'text-image--left' : '')) . '"><div class="text-image__media">' . (($this->image !== null) ? '<img' . (($this->image?->src) !== null ? (((($temp = $this->image?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->image?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 900px) 50vw, 100vw"' . (($this->image?->alt) !== null ? (((($temp = $this->image?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->image?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" />' : '<div class="media-placeholder"></div>') . '</div><div class="text-image__body"><p class="kicker">' . ((($temp = $this->kicker) === null) ? '' : $temp->render()) . '</p><h2 class="text-image__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="prose">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</div></div></div>';
    }
}
