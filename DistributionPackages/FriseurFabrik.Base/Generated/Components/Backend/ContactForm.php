<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class ContactForm implements _\ComponentInterface
{
    private function __construct(
        private string $action,
        private string $subject,
        private bool $hasEmail,
        private string $phoneLabel,
        private string $phoneHref,
        private ?string $whatsappHref,
        private Csrf $_19_10_Csrf,
    ) {
    }

    public static function create(
        string $action,
        string $csrf,
        string $subject,
        bool $hasEmail,
        string $phoneLabel,
        string $phoneHref,
        ?string $whatsappHref,
    ): self {
        return new self(
            action: $action,
            subject: $subject,
            hasEmail: $hasEmail,
            phoneLabel: $phoneLabel,
            phoneHref: $phoneHref,
            whatsappHref: $whatsappHref,
            _19_10_Csrf: Csrf::create(
                token: $csrf,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="ff-panel"><h3 class="ff-panel__title">Kund:in kontaktieren</h3><p class="ff-contact-buttons"><a class="neos-button" href="' . _\Util::escapeAttributeValue($this->phoneHref) . '">Anrufen: ' . _\Util::escapeText($this->phoneLabel) . '</a>' . (($this->whatsappHref !== null) ? '<a class="neos-button"' . (($this->whatsappHref) !== null ? (((($temp = $this->whatsappHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">WhatsApp</a>' : '') . '</p>' . ($this->hasEmail ? '<form class="ff-form" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '">' . $this->_19_10_Csrf->render() . '<label>Betreff<input type="text" name="moduleArguments[subject]" value="' . _\Util::escapeAttributeValue($this->subject) . '" required maxlength="150" /></label><label>Nachricht<textarea name="moduleArguments[message]" rows="4" required></textarea></label><button class="neos-button neos-button-primary" type="submit">E-Mail senden</button></form>' : '<p class="ff-muted">Keine E-Mail-Adresse angegeben – bitte telefonisch kontaktieren.</p>') . '</div>';
    }
}
