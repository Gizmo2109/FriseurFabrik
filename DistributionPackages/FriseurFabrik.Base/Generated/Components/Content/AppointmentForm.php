<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Atom\SelectGroup;
use FriseurFabrik\Base\Components\Atom\SelectOption;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AppointmentForm implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<SelectGroup> $services
     * @param _\ComponentListInterface<SelectOption> $stylists
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private ?_\ComponentInterface $intro,
        private ?_\ComponentInterface $successText,
        private string $action,
        private string $site,
        private string $returnUri,
        private _\ComponentListInterface $services,
        private _\ComponentListInterface $stylists,
        private string $minDate,
        private string $closedDays,
        private ?string $privacyHref,
        private ?string $phoneLabel,
        private ?string $phoneHref,
        private Icon $_25_103_Icon,
        private Icon $_28_87_Icon,
        private Icon $_99_78_Icon,
    ) {
    }

    /**
     * @param _\ComponentListInterface<SelectGroup> $services
     * @param _\ComponentListInterface<SelectOption> $stylists
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentInterface|string|null $intro,
        _\ComponentInterface|string|null $successText,
        string $action,
        string $site,
        string $returnUri,
        _\ComponentListInterface $services,
        _\ComponentListInterface $stylists,
        string $minDate,
        string $closedDays,
        ?string $privacyHref,
        ?string $phoneLabel,
        ?string $phoneHref,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            intro: is_string($intro) ? _\StringComponent::fromString($intro) : $intro,
            successText: is_string($successText) ? _\StringComponent::fromString($successText) : $successText,
            action: $action,
            site: $site,
            returnUri: $returnUri,
            services: $services,
            stylists: $stylists,
            minDate: $minDate,
            closedDays: $closedDays,
            privacyHref: $privacyHref,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            _25_103_Icon: Icon::create(
                name: IconName::PHONE,
            ),
            _28_87_Icon: Icon::create(
                name: IconName::CHECK,
            ),
            _99_78_Icon: Icon::create(
                name: IconName::CALENDAR,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="appointment"><div class="appointment__intro"><h2 class="section-head__title">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</h2><div class="prose">' . ((($temp = $this->intro) === null) ? '' : $temp->render()) . '</div>' . (($this->phoneHref !== null) ? '<p class="appointment__phone">Lieber persönlich? <a' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_25_103_Icon->render() . (($this->phoneLabel) !== null ? (((($temp = $this->phoneLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a></p>' : '') . '</div><div class="appointment__form"><div id="termin-gesendet" class="form-notice form-notice--success" role="status">' . $this->_28_87_Icon->render() . '<div>' . ((($temp = $this->successText) === null) ? '' : $temp->render()) . '</div></div><div id="termin-fehler" class="form-notice form-notice--error" role="alert">Ihre Anfrage konnte nicht gesendet werden. Bitte prüfen Sie Name, Telefon, den Wunschtermin (nicht an Ruhetagen, nicht in der Vergangenheit) und die Datenschutz-Zustimmung – oder rufen Sie uns an.</div><form class="form" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '" data-appointment-form data-closed-days="' . _\Util::escapeAttributeValue($this->closedDays) . '" novalidate><input type="hidden" name="site" value="' . _\Util::escapeAttributeValue($this->site) . '" /><input type="hidden" name="returnUri" value="' . _\Util::escapeAttributeValue($this->returnUri) . '" /><div class="form__honeypot" aria-hidden="true"><label for="appt-website">Website</label><input id="appt-website" type="text" name="website" tabindex="-1" autocomplete="off" /></div><div class="form__row form__row--2"><div class="field"><label for="appt-name">Name *</label><input id="appt-name" type="text" name="name" required maxlength="120" autocomplete="name" /><p class="field__error" data-error-for="name"></p></div><div class="field"><label for="appt-phone">Telefon *</label><input id="appt-phone" type="tel" name="phone" required maxlength="40" autocomplete="tel" /><p class="field__error" data-error-for="phone"></p></div></div><div class="field"><label for="appt-email">E-Mail (optional)</label><input id="appt-email" type="email" name="email" maxlength="160" autocomplete="email" /><p class="field__error" data-error-for="email"></p></div><div class="form__row form__row--2"><div class="field"><label for="appt-service">Gewünschte Leistung</label><select id="appt-service" name="service"><option value="">Bitte wählen / Beratung</option>' . $this->services->render() . '</select></div>' . ((count($this->stylists) > 0) ? '<div class="field"><label for="appt-stylist">Wunsch-Stylist:in</label><select id="appt-stylist" name="stylist"><option value="">Egal – wer frei ist</option>' . $this->stylists->render() . '</select></div>' : '') . '</div><div class="form__row form__row--2"><div class="field"><label for="appt-date">Wunschtermin</label><input id="appt-date" type="date" name="date" min="' . _\Util::escapeAttributeValue($this->minDate) . '" /><p class="field__error" data-error-for="date"></p></div><div class="field"><label for="appt-time">Tageszeit</label><select id="appt-time" name="time"><option value="">Flexibel</option><option value="vormittags">Vormittags</option><option value="mittags">Mittags</option><option value="nachmittags">Nachmittags</option><option value="abends">Abends</option></select></div></div><div class="field"><label for="appt-message">Nachricht (optional)</label><textarea id="appt-message" name="message" rows="4" maxlength="2000" placeholder="z.B. Haarlänge, Farbwunsch, Alternativtermine …"></textarea></div><div class="field field--check"><input id="appt-privacy" type="checkbox" name="privacy" value="1" required /><label for="appt-privacy">Ich bin einverstanden, dass meine Angaben zur Bearbeitung der Terminanfrage gespeichert werden. ' . (($this->privacyHref !== null) ? '<a' . (($this->privacyHref) !== null ? (((($temp = $this->privacyHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>Datenschutzerklärung</a>' : '') . ' *</label></div><p class="field__error" data-error-for="privacy"></p><p class="field__error field__error--form" data-error-for="form"></p><button class="btn btn--primary btn--large" type="submit" data-submit>' . $this->_99_78_Icon->render() . 'Termin anfragen</button><p class="form__hint">Die Anfrage ist noch keine Terminbestätigung – wir melden uns schnellstmöglich bei Ihnen.</p></form></div></div>';
    }
}
