<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\FactRow;
use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AppointmentAnswer implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<FactRow> $facts
     */
    private function __construct(
        private string $heading,
        private string $intro,
        private ?string $statusLabel,
        private _\ComponentListInterface $facts,
        private ?string $notice,
        private string $action,
        private string $token,
        private bool $canAccept,
        private bool $canCancel,
        private ?string $phoneLabel,
        private ?string $phoneHref,
        private Icon $_23_98_Icon,
        private Icon $_32_74_Icon,
        private Icon $_43_107_Icon,
    ) {
    }

    /**
     * @param _\ComponentListInterface<FactRow> $facts
     */
    public static function create(
        string $heading,
        string $intro,
        ?string $statusLabel,
        _\ComponentListInterface $facts,
        ?string $notice,
        string $action,
        string $token,
        bool $canAccept,
        bool $canCancel,
        ?string $phoneLabel,
        ?string $phoneHref,
    ): self {
        return new self(
            heading: $heading,
            intro: $intro,
            statusLabel: $statusLabel,
            facts: $facts,
            notice: $notice,
            action: $action,
            token: $token,
            canAccept: $canAccept,
            canCancel: $canCancel,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            _23_98_Icon: Icon::create(
                name: IconName::CHECK,
            ),
            _32_74_Icon: Icon::create(
                name: IconName::CHECK,
            ),
            _43_107_Icon: Icon::create(
                name: IconName::PHONE,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<section class="section section--muted section--answer"><div class="container"><div class="answer"><p class="kicker">Ihre Terminanfrage</p><h1 class="answer__title">' . _\Util::escapeText($this->heading) . '</h1>' . (($this->notice !== null) ? '<div class="form-notice form-notice--success is-visible" role="status">' . $this->_23_98_Icon->render() . '<div>' . (($this->notice) !== null ? (((($temp = $this->notice) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</div></div>' : '') . '<p class="answer__intro">' . _\Util::escapeText($this->intro) . '</p>' . (($this->statusLabel !== null) ? '<p class="answer__status">Status: <strong>' . (($this->statusLabel) !== null ? (((($temp = $this->statusLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</strong></p>' : '') . '<dl class="facts">' . $this->facts->render() . '</dl><div class="answer__actions">' . ($this->canAccept ? '<form method="post" action="' . _\Util::escapeAttributeValue($this->action) . '"><input type="hidden" name="token" value="' . _\Util::escapeAttributeValue($this->token) . '" /><input type="hidden" name="decision" value="accept" /><button class="btn btn--primary btn--large" type="submit">' . $this->_32_74_Icon->render() . 'Termin annehmen</button></form>' : '') . ($this->canCancel ? '<form method="post" action="' . _\Util::escapeAttributeValue($this->action) . '" data-confirm="Möchten Sie den Termin wirklich absagen?"><input type="hidden" name="token" value="' . _\Util::escapeAttributeValue($this->token) . '" /><input type="hidden" name="decision" value="cancel" /><button class="btn btn--secondary btn--large" type="submit">' . ($this->canAccept ? 'Vorschlag ablehnen' : 'Termin absagen') . '</button></form>' : '') . '</div>' . (($this->phoneHref !== null) ? '<p class="answer__phone">Fragen? Rufen Sie uns an: <a' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_43_107_Icon->render() . (($this->phoneLabel) !== null ? (((($temp = $this->phoneLabel) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</a></p>' : '') . '</div></div></section>';
    }
}
