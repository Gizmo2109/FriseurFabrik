<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Content\GalleryItem;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Gallery implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<GalleryItem> $items
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $intro,
        private _\ComponentListInterface $items,
        private bool $mosaic,
    ) {
    }

    /**
     * @param _\ComponentListInterface<GalleryItem> $items
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $intro,
        _\ComponentListInterface $items,
        bool $mosaic,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            intro: is_string($intro) ? _\StringComponent::fromString($intro) : $intro,
            items: $items,
            mosaic: $mosaic,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="gallery-block"><header class="section-head"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="section-head__intro">' . ((($temp = $this->intro) === null) ? '' : $temp->render()) . '</div></header>' . ((count($this->items) > 0) ? '<div class="' . _\Util::joinAttributeValues('gallery', ($this->mosaic ? 'gallery--mosaic' : '')) . '">' . $this->items->render() . '</div>' : '<p class="empty-hint">Noch keine Bilder ausgewählt.</p>') . '</div>';
    }
}
