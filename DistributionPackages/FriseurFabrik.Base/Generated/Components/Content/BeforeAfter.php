<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class BeforeAfter implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
        private ?Img $before,
        private ?Img $after,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
        ?Img $before,
        ?Img $after,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            before: $before,
            after: $after,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="before-after"><header class="section-head"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="section-head__intro">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</div></header>' . ((($this->before !== null) && ($this->after !== null)) ? '<div class="compare" data-compare><img class="compare__img"' . (($this->before?->src) !== null ? (((($temp = $this->before?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->before?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 1200px) 1100px, 100vw"' . (($this->before?->alt) !== null ? (((($temp = $this->before?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->before?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->before?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" /><div class="compare__after"><img class="compare__img"' . (($this->after?->src) !== null ? (((($temp = $this->after?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->after?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 1200px) 1100px, 100vw"' . (($this->after?->alt) !== null ? (((($temp = $this->after?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->after?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->after?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" /></div><span class="compare__label compare__label--before">Vorher</span><span class="compare__label compare__label--after">Nachher</span><span class="compare__handle" aria-hidden="true"></span><input class="compare__range" type="range" min="0" max="100" value="50" aria-label="Vorher-Nachher-Vergleich verschieben" /></div>' : '<p class="empty-hint">Bitte im Inspector ein Vorher- und ein Nachher-Foto auswählen.</p>') . '</div>';
    }
}
