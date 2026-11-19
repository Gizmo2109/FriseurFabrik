<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use FriseurFabrik\Base\Components\Backend\Option;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class NewAppointmentForm implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<Option> $sites
     * @param _\ComponentListInterface<Option> $durations
     */
    private function __construct(
        private string $action,
        private string $backHref,
        private _\ComponentListInterface $sites,
        private bool $showSites,
        private string $date,
        private _\ComponentListInterface $durations,
        private Csrf $_18_6_Csrf,
    ) {
    }

    /**
     * @param _\ComponentListInterface<Option> $sites
     * @param _\ComponentListInterface<Option> $durations
     */
    public static function create(
        string $action,
        string $backHref,
        string $csrf,
        _\ComponentListInterface $sites,
        bool $showSites,
        string $date,
        _\ComponentListInterface $durations,
    ): self {
        return new self(
            action: $action,
            backHref: $backHref,
            sites: $sites,
            showSites: $showSites,
            date: $date,
            durations: $durations,
            _18_6_Csrf: Csrf::create(
                token: $csrf,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="ff-detail"><p><a class="ff-link" href="' . _\Util::escapeAttributeValue($this->backHref) . '">← Zurück zur Übersicht</a></p><h2 class="ff-detail__name">Termin eintragen</h2><p class="ff-muted">Für Termine, die telefonisch oder im Salon vereinbart wurden. Der Termin wird direkt als bestätigt gespeichert.</p><form class="ff-panel ff-form ff-form--wide" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '">' . $this->_18_6_Csrf->render() . ($this->showSites ? '<label>Salon<select name="moduleArguments[site]" required>' . $this->sites->render() . '</select></label>' : '') . '<div class="ff-form__row"><label>Name *<input type="text" name="moduleArguments[name]" required maxlength="120" /></label><label>Telefon *<input type="tel" name="moduleArguments[phone]" required maxlength="40" /></label><label>E-Mail<input type="email" name="moduleArguments[email]" maxlength="160" /></label></div><div class="ff-form__row"><label>Leistung<input type="text" name="moduleArguments[service]" maxlength="200" placeholder="z.B. Damen: Schnitt &amp; Föhnen" /></label><label>Stylist:in<input type="text" name="moduleArguments[stylist]" maxlength="120" /></label></div><div class="ff-form__row"><label>Datum *<input type="date" name="moduleArguments[date]" value="' . _\Util::escapeAttributeValue($this->date) . '" required /></label><label>Uhrzeit *<input type="time" name="moduleArguments[time]" step="900" required /></label><label>Dauer<select name="moduleArguments[duration]">' . $this->durations->render() . '</select></label></div><label>Notiz<textarea name="moduleArguments[message]" rows="2"></textarea></label><label class="ff-check"><input type="checkbox" name="moduleArguments[notify]" value="1" checked /> Bestätigung per E-Mail senden (falls E-Mail angegeben)</label><button class="neos-button neos-button-success" type="submit">Termin speichern</button></form></div>';
    }
}
