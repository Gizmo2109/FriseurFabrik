<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class NoteForm implements _\ComponentInterface
{
    private function __construct(
        private string $action,
        private string $note,
        private Csrf $_8_4_Csrf,
    ) {
    }

    public static function create(
        string $action,
        string $csrf,
        string $note,
    ): self {
        return new self(
            action: $action,
            note: $note,
            _8_4_Csrf: Csrf::create(
                token: $csrf,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<form class="ff-panel ff-form" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '">' . $this->_8_4_Csrf->render() . '<h3 class="ff-panel__title">Interne Notiz</h3><p class="ff-muted">Nur im Backend sichtbar.</p><textarea name="moduleArguments[note]" rows="3">' . _\Util::escapeText($this->note) . '</textarea><button class="neos-button" type="submit">Notiz speichern</button></form>';
    }
}
