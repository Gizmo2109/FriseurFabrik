<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use FriseurFabrik\Base\Components\Backend\Option;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class TimeForm implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<Option> $durations
     * @param _\ComponentListInterface<Option> $stylists
     */
    private function __construct(
        private string $title,
        private string $hint,
        private string $action,
        private string $date,
        private string $time,
        private _\ComponentListInterface $durations,
        private _\ComponentListInterface $stylists,
        private string $messagePlaceholder,
        private string $buttonLabel,
        private bool $primary,
        private Csrf $_18_4_Csrf,
    ) {
    }

    /**
     * @param _\ComponentListInterface<Option> $durations
     * @param _\ComponentListInterface<Option> $stylists
     */
    public static function create(
        string $title,
        string $hint,
        string $action,
        string $csrf,
        string $date,
        string $time,
        _\ComponentListInterface $durations,
        _\ComponentListInterface $stylists,
        string $messagePlaceholder,
        string $buttonLabel,
        bool $primary,
    ): self {
        return new self(
            title: $title,
            hint: $hint,
            action: $action,
            date: $date,
            time: $time,
            durations: $durations,
            stylists: $stylists,
            messagePlaceholder: $messagePlaceholder,
            buttonLabel: $buttonLabel,
            primary: $primary,
            _18_4_Csrf: Csrf::create(
                token: $csrf,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<form class="ff-panel ff-form" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '">' . $this->_18_4_Csrf->render() . '<h3 class="ff-panel__title">' . _\Util::escapeText($this->title) . '</h3><p class="ff-muted">' . _\Util::escapeText($this->hint) . '</p><div class="ff-form__row"><label>Datum<input type="date" name="moduleArguments[date]" value="' . _\Util::escapeAttributeValue($this->date) . '" required /></label><label>Uhrzeit<input type="time" name="moduleArguments[time]" value="' . _\Util::escapeAttributeValue($this->time) . '" step="900" required /></label><label>Dauer<select name="moduleArguments[duration]">' . $this->durations->render() . '</select></label></div>' . ((count($this->stylists) > 0) ? '<label>Stylist:in<select name="moduleArguments[stylist]"><option value="">– egal –</option>' . $this->stylists->render() . '</select></label>' : '') . '<label>Nachricht an die Kundin / den Kunden (optional)<textarea name="moduleArguments[message]" rows="3" placeholder="' . _\Util::escapeAttributeValue($this->messagePlaceholder) . '"></textarea></label><button class="' . _\Util::joinAttributeValues('neos-button', ($this->primary ? 'neos-button-success' : 'neos-button-primary')) . '" type="submit">' . _\Util::escapeText($this->buttonLabel) . '</button></form>';
    }
}
