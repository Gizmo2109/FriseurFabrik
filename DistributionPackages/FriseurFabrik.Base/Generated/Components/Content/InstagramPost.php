<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class InstagramPost implements _\ComponentInterface
{
    private function __construct(
        private ?string $postUrl,
        private ?Img $image,
        private ?_\ComponentInterface $caption,
        private Icon $_14_47_Icon,
        private Icon $_21_108_Icon,
    ) {
    }

    public static function create(
        ?string $postUrl,
        ?Img $image,
        _\ComponentInterface|string|null $caption,
    ): self {
        return new self(
            postUrl: $postUrl,
            image: $image,
            caption: is_string($caption) ? _\StringComponent::fromString($caption) : $caption,
            _14_47_Icon: Icon::create(
                name: IconName::INSTAGRAM,
            ),
            _21_108_Icon: Icon::create(
                name: IconName::ARROW,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<article class="insta-post"' . ((($temp = $this->postUrl) === null) ? '' : ' data-instagram-post="' . _\Util::escapeAttributeValue($temp) . '"') . '><div class="insta-post__preview">' . (($this->image !== null) ? '<img' . (($this->image?->src) !== null ? (((($temp = $this->image?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . (($this->image?->alt) !== null ? (((($temp = $this->image?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->image?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" />' : '<div class="insta-post__placeholder">' . $this->_14_47_Icon->render() . '</div>') . '</div><div class="insta-post__body"><p class="insta-post__caption">' . ((($temp = $this->caption) === null) ? '' : $temp->render()) . '</p>' . (($this->postUrl !== null) ? '<div class="insta-post__actions"><button class="btn btn--primary btn--small" type="button" data-instagram-load>Beitrag laden</button><a class="link-arrow"' . (($this->postUrl) !== null ? (((($temp = $this->postUrl) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">Auf Instagram ansehen ' . $this->_21_108_Icon->render() . '</a></div>' : '<p class="empty-hint">Bitte im Inspector den Link zum Beitrag eintragen.</p>') . '</div></article>';
    }
}
