<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class ImageCard implements _\ComponentInterface
{
    private function __construct(
        private ?Img $image,
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
        private ?string $href,
        private Icon $_17_94_Icon,
    ) {
    }

    public static function create(
        ?Img $image,
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
        ?string $href,
    ): self {
        return new self(
            image: $image,
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            href: $href,
            _17_94_Icon: Icon::create(
                name: IconName::ARROW,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<article class="image-card"><div class="image-card__media">' . (($this->image !== null) ? '<img' . (($this->image?->src) !== null ? (((($temp = $this->image?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->image?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 900px) 33vw, 100vw"' . (($this->image?->alt) !== null ? (((($temp = $this->image?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->image?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" />' : '<div class="media-placeholder"></div>') . '</div><div class="image-card__body"><h3 class="image-card__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h3><p class="image-card__text">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</p>' . (($this->href !== null) ? '<a class="link-arrow image-card__link"' . (($this->href) !== null ? (((($temp = $this->href) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>Mehr erfahren ' . $this->_17_94_Icon->render() . '</a>' : '') . '</div></article>';
    }
}
