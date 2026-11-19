<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class InstagramPosts implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $items,
        private ?string $profileHref,
        private ?string $handle,
        private Icon $_12_116_Icon,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $items,
        ?string $profileHref,
        ?string $handle,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            items: is_string($items) ? _\StringComponent::fromString($items) : $items,
            profileHref: $profileHref,
            handle: $handle,
            _12_116_Icon: Icon::create(
                name: IconName::INSTAGRAM,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="insta-posts"><header class="insta__head"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2>' . (($this->profileHref !== null) ? '<a class="btn btn--secondary"' . (($this->profileHref) !== null ? (((($temp = $this->profileHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">' . $this->_12_116_Icon->render() . (($this->handle) !== null ? (((($temp = $this->handle) === null) ? '' : _\Util::escapeText($temp))) : ('Instagram')) . '</a>' : '') . '</header>' . ((($temp = $this->items) === null) ? '' : $temp->render()) . '<p class="insta-posts__notice">Beim Laden eines Beitrags werden Daten an Instagram (Meta Platforms) übertragen.</p></div>';
    }
}
