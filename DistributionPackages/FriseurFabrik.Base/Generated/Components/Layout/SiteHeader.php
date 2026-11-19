<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class SiteHeader implements _\ComponentInterface
{
    private function __construct(
        private string $homeHref,
        private string $salonName,
        private ?string $tagline,
        private ?Img $logo,
        private ?_\ComponentInterface $menu,
        private ?string $ctaLabel,
        private ?string $ctaHref,
        private ?string $phoneLabel,
        private ?string $phoneHref,
        private Icon $_24_39_Icon,
        private Icon $_25_40_Icon,
        private Icon $_31_82_Icon,
    ) {
    }

    public static function create(
        string $homeHref,
        string $salonName,
        ?string $tagline,
        ?Img $logo,
        _\ComponentInterface|string|null $menu,
        ?string $ctaLabel,
        ?string $ctaHref,
        ?string $phoneLabel,
        ?string $phoneHref,
    ): self {
        return new self(
            homeHref: $homeHref,
            salonName: $salonName,
            tagline: $tagline,
            logo: $logo,
            menu: is_string($menu) ? _\StringComponent::fromString($menu) : $menu,
            ctaLabel: $ctaLabel,
            ctaHref: $ctaHref,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            _24_39_Icon: Icon::create(
                name: IconName::MENU,
            ),
            _25_40_Icon: Icon::create(
                name: IconName::CLOSE,
            ),
            _31_82_Icon: Icon::create(
                name: IconName::PHONE,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<header class="site-header" data-site-header><div class="container site-header__inner"><a class="brand" href="' . _\Util::escapeAttributeValue($this->homeHref) . '">' . (($this->logo !== null) ? '<img class="brand__logo"' . (($this->logo?->src) !== null ? (((($temp = $this->logo?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ' alt="' . _\Util::escapeAttributeValue($this->salonName) . '"' . ((($temp = $this->logo?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->logo?->height) === null) ? '' : ' height="' . $temp . '"') . ' />' : '<span class="brand__text"><span class="brand__name">' . _\Util::escapeText($this->salonName) . '</span>' . (($this->tagline !== null) ? '<span class="brand__tagline">' . (($this->tagline) !== null ? (((($temp = $this->tagline) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</span>' : '') . '</span>') . '</a><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle><span class="nav-toggle__open">' . $this->_24_39_Icon->render() . '</span><span class="nav-toggle__close">' . $this->_25_40_Icon->render() . '</span><span class="sr-only">Menü</span></button><nav id="site-nav" class="site-nav" aria-label="Hauptnavigation">' . ((($temp = $this->menu) === null) ? '' : $temp->render()) . '<div class="site-nav__actions">' . (($this->phoneHref !== null) ? '<a class="site-nav__phone"' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_31_82_Icon->render() . '<span>' . (($this->phoneLabel) !== null ? (((($temp = $this->phoneLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</span></a>' : '') . (($this->ctaHref !== null) ? '<a class="btn btn--primary"' . (($this->ctaHref) !== null ? (((($temp = $this->ctaHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . (($this->ctaLabel) !== null ? (((($temp = $this->ctaLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a>' : '') . '</div></nav></div></header>';
    }
}
