<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Hero implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $kicker,
        private ?_\ComponentInterface $headline,
        private ?_\ComponentInterface $text,
        private ?Img $image,
        private bool $large,
        private ?string $bookingLabel,
        private ?string $bookingHref,
        private ?string $phoneLabel,
        private ?string $phoneHref,
        private Icon $_22_96_Icon,
        private Icon $_23_90_Icon,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $kicker,
        _\ComponentInterface|string|null $headline,
        _\ComponentInterface|string|null $text,
        ?Img $image,
        bool $large,
        ?string $bookingLabel,
        ?string $bookingHref,
        ?string $phoneLabel,
        ?string $phoneHref,
    ): self {
        return new self(
            kicker: is_string($kicker) ? _\StringComponent::fromString($kicker) : $kicker,
            headline: is_string($headline) ? _\StringComponent::fromString($headline) : $headline,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            image: $image,
            large: $large,
            bookingLabel: $bookingLabel,
            bookingHref: $bookingHref,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            _22_96_Icon: Icon::create(
                name: IconName::CALENDAR,
            ),
            _23_90_Icon: Icon::create(
                name: IconName::PHONE,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<section class="' . _\Util::joinAttributeValues('hero', ($this->large ? 'hero--large' : 'hero--small'), (($this->image !== null) ? 'hero--image' : 'hero--plain')) . '">' . (($this->image !== null) ? '<img class="hero__image"' . (($this->image?->src) !== null ? (((($temp = $this->image?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->image?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="100vw"' . (($this->image?->alt) !== null ? (((($temp = $this->image?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->image?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->image?->height) === null) ? '' : ' height="' . $temp . '"') . ' fetchpriority="high" />' : '') . '<div class="container hero__content"><p class="hero__kicker">' . ((($temp = $this->kicker) === null) ? '' : $temp->render()) . '</p><h1 class="hero__headline">' . ((($temp = $this->headline) === null) ? '' : $temp->render()) . '</h1><div class="hero__text">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</div><div class="hero__actions">' . (($this->bookingHref !== null) ? '<a class="btn btn--primary btn--large"' . (($this->bookingHref) !== null ? (((($temp = $this->bookingHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_22_96_Icon->render() . (($this->bookingLabel) !== null ? (((($temp = $this->bookingLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a>' : '') . (($this->phoneHref !== null) ? '<a class="btn btn--light btn--large"' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_23_90_Icon->render() . (($this->phoneLabel) !== null ? (((($temp = $this->phoneLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a>' : '') . '</div></div></section>';
    }
}
