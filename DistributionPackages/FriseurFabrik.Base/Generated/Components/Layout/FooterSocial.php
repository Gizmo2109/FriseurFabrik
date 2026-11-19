<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use FriseurFabrik\Base\Components\Atom\SocialLink;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FooterSocial implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<SocialLink> $social
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private _\ComponentListInterface $social,
        private ?string $ctaLabel,
        private ?string $ctaHref,
    ) {
    }

    /**
     * @param _\ComponentListInterface<SocialLink> $social
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentListInterface $social,
        ?string $ctaLabel,
        ?string $ctaHref,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            social: $social,
            ctaLabel: $ctaLabel,
            ctaHref: $ctaHref,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="footer-col"><p class="site-footer__heading">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</p>' . ((count($this->social) > 0) ? '<div class="social">' . $this->social->render() . '</div>' : '') . (($this->ctaHref !== null) ? '<a class="btn btn--primary btn--small"' . (($this->ctaHref) !== null ? (((($temp = $this->ctaHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . (($this->ctaLabel) !== null ? (((($temp = $this->ctaLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a>' : '') . '</div>';
    }
}
