<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FooterContact implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $title,
        private string $salonName,
        private ?string $street,
        private ?string $zipCity,
        private ?string $phoneLabel,
        private ?string $phoneHref,
        private ?string $email,
        private ?string $emailHref,
        private ?string $mapsHref,
        private Icon $_22_58_Icon,
        private Icon $_23_58_Icon,
        private Icon $_25_113_Icon,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $title,
        string $salonName,
        ?string $street,
        ?string $zipCity,
        ?string $phoneLabel,
        ?string $phoneHref,
        ?string $email,
        ?string $emailHref,
        ?string $mapsHref,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            salonName: $salonName,
            street: $street,
            zipCity: $zipCity,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            email: $email,
            emailHref: $emailHref,
            mapsHref: $mapsHref,
            _22_58_Icon: Icon::create(
                name: IconName::PHONE,
            ),
            _23_58_Icon: Icon::create(
                name: IconName::MAIL,
            ),
            _25_113_Icon: Icon::create(
                name: IconName::ARROW,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="footer-col"><p class="site-footer__heading">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</p><address class="site-footer__address"><strong>' . _\Util::escapeText($this->salonName) . '</strong>' . (($this->street !== null) ? '<span>' . (($this->street) !== null ? (((($temp = $this->street) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</span>' : '') . (($this->zipCity !== null) ? '<span>' . (($this->zipCity) !== null ? (((($temp = $this->zipCity) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</span>' : '') . '</address><ul class="contact-list">' . (($this->phoneHref !== null) ? '<li><a' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_22_58_Icon->render() . (($this->phoneLabel) !== null ? (((($temp = $this->phoneLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a></li>' : '') . (($this->emailHref !== null) ? '<li><a' . (($this->emailHref) !== null ? (((($temp = $this->emailHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_23_58_Icon->render() . (($this->email) !== null ? (((($temp = $this->email) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a></li>' : '') . '</ul>' . (($this->mapsHref !== null) ? '<a class="link-arrow"' . (($this->mapsHref) !== null ? (((($temp = $this->mapsHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">Route planen ' . $this->_25_113_Icon->render() . '</a>' : '') . '</div>';
    }
}
