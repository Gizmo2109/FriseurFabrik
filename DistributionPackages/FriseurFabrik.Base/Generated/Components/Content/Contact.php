<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\HoursRow;
use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Contact implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<HoursRow> $hours
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $text,
        private string $salonName,
        private ?string $street,
        private ?string $zipCity,
        private ?string $phoneLabel,
        private ?string $phoneHref,
        private ?string $email,
        private ?string $emailHref,
        private ?string $whatsappHref,
        private ?string $mapsHref,
        private ?string $parkingInfo,
        private _\ComponentListInterface $hours,
        private ?string $hoursNote,
        private Icon $_25_12_Icon,
        private Icon $_26_34_Icon,
        private Icon $_27_34_Icon,
        private Icon $_28_37_Icon,
        private Icon $_30_58_Icon,
        private Icon $_31_110_Icon,
        private Icon $_34_36_Icon,
    ) {
    }

    /**
     * @param _\ComponentListInterface<HoursRow> $hours
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $text,
        string $salonName,
        ?string $street,
        ?string $zipCity,
        ?string $phoneLabel,
        ?string $phoneHref,
        ?string $email,
        ?string $emailHref,
        ?string $whatsappHref,
        ?string $mapsHref,
        ?string $parkingInfo,
        _\ComponentListInterface $hours,
        ?string $hoursNote,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            text: is_string($text) ? _\StringComponent::fromString($text) : $text,
            salonName: $salonName,
            street: $street,
            zipCity: $zipCity,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            email: $email,
            emailHref: $emailHref,
            whatsappHref: $whatsappHref,
            mapsHref: $mapsHref,
            parkingInfo: $parkingInfo,
            hours: $hours,
            hoursNote: $hoursNote,
            _25_12_Icon: Icon::create(
                name: IconName::PIN,
            ),
            _26_34_Icon: Icon::create(
                name: IconName::PHONE,
            ),
            _27_34_Icon: Icon::create(
                name: IconName::MAIL,
            ),
            _28_37_Icon: Icon::create(
                name: IconName::WHATSAPP,
            ),
            _30_58_Icon: Icon::create(
                name: IconName::PARKING,
            ),
            _31_110_Icon: Icon::create(
                name: IconName::PIN,
            ),
            _34_36_Icon: Icon::create(
                name: IconName::CLOCK,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="contact"><div class="contact__info"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="prose">' . ((($temp = $this->text) === null) ? '' : $temp->render()) . '</div><ul class="contact-list contact-list--large"><li>' . $this->_25_12_Icon->render() . '<span><strong>' . _\Util::escapeText($this->salonName) . '</strong><br />' . (($this->street) !== null ? (((($temp = $this->street) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '<br />' . (($this->zipCity) !== null ? (((($temp = $this->zipCity) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</span></li>' . (($this->phoneHref !== null) ? '<li>' . $this->_26_34_Icon->render() . '<a' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . (($this->phoneLabel) !== null ? (((($temp = $this->phoneLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a></li>' : '') . (($this->emailHref !== null) ? '<li>' . $this->_27_34_Icon->render() . '<a' . (($this->emailHref) !== null ? (((($temp = $this->emailHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . (($this->email) !== null ? (((($temp = $this->email) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a></li>' : '') . (($this->whatsappHref !== null) ? '<li>' . $this->_28_37_Icon->render() . '<a' . (($this->whatsappHref) !== null ? (((($temp = $this->whatsappHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">WhatsApp schreiben</a></li>' : '') . '</ul>' . (($this->parkingInfo !== null) ? '<p class="contact__parking">' . $this->_30_58_Icon->render() . (($this->parkingInfo) !== null ? (((($temp = $this->parkingInfo) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</p>' : '') . (($this->mapsHref !== null) ? '<a class="btn btn--secondary"' . (($this->mapsHref) !== null ? (((($temp = $this->mapsHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">' . $this->_31_110_Icon->render() . 'Route planen</a>' : '') . '</div><div class="contact__hours"><h3 class="contact__subtitle">' . $this->_34_36_Icon->render() . 'Öffnungszeiten</h3><dl class="hours">' . $this->hours->render() . '</dl>' . (($this->hoursNote !== null) ? '<p class="opening-hours__note">' . (($this->hoursNote) !== null ? (((($temp = $this->hoursNote) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</p>' : '') . '</div></div>';
    }
}
