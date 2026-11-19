<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Content\InstagramTile;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Instagram implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<InstagramTile> $tiles
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?string $handle,
        private ?string $profileHref,
        private _\ComponentListInterface $tiles,
        private Icon $_13_116_Icon,
    ) {
    }

    /**
     * @param _\ComponentListInterface<InstagramTile> $tiles
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        ?string $handle,
        ?string $profileHref,
        _\ComponentListInterface $tiles,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            handle: $handle,
            profileHref: $profileHref,
            tiles: $tiles,
            _13_116_Icon: Icon::create(
                name: IconName::INSTAGRAM,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="insta"><header class="insta__head"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2>' . (($this->profileHref !== null) ? '<a class="btn btn--secondary"' . (($this->profileHref) !== null ? (((($temp = $this->profileHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">' . $this->_13_116_Icon->render() . (($this->handle) !== null ? (((($temp = $this->handle) === null) ? '' : _\Util::escapeText($temp))) : ('Instagram')) . '</a>' : '') . '</header>' . ((count($this->tiles) > 0) ? '<div class="insta__grid">' . $this->tiles->render() . '</div>' : '<p class="empty-hint">Noch keine Instagram-Bilder ausgewählt.</p>') . '</div>';
    }
}
