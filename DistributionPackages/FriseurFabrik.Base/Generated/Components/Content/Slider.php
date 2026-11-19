<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Content\SlideItem;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Slider implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<SlideItem> $slides
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private _\ComponentListInterface $slides,
        private Icon $_14_124_Icon,
        private Icon $_15_101_Icon,
    ) {
    }

    /**
     * @param _\ComponentListInterface<SlideItem> $slides
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentListInterface $slides,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            slides: $slides,
            _14_124_Icon: Icon::create(
                name: IconName::ARROW,
            ),
            _15_101_Icon: Icon::create(
                name: IconName::ARROW,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="slider" data-slider><header class="slider__head"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2>' . ((count($this->slides) > 1) ? '<div class="slider__controls"><button class="slider__button slider__button--prev" type="button" data-slider-prev aria-label="Vorheriges Bild">' . $this->_14_124_Icon->render() . '</button><button class="slider__button" type="button" data-slider-next aria-label="Nächstes Bild">' . $this->_15_101_Icon->render() . '</button></div>' : '') . '</header>' . ((count($this->slides) > 0) ? '<div class="slider__track" data-slider-track>' . $this->slides->render() . '</div>' : '<p class="empty-hint">Bitte im Inspector Fotos auswählen.</p>') . '</div>';
    }
}
