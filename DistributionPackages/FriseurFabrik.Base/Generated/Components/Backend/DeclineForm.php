<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class DeclineForm implements _\ComponentInterface
{
    private function __construct(
        private string $action,
        private Csrf $_7_4_Csrf,
    ) {
    }

    public static function create(
        string $action,
        string $csrf,
    ): self {
        return new self(
            action: $action,
            _7_4_Csrf: Csrf::create(
                token: $csrf,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<form class="ff-panel ff-form" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '" data-confirm="Anfrage wirklich absagen? Die Kundin / der Kunde wird informiert.">' . $this->_7_4_Csrf->render() . '<h3 class="ff-panel__title">Absagen</h3><label>Begründung / Nachricht (optional)<textarea name="moduleArguments[message]" rows="2" placeholder="z.B. Leider sind wir in dieser Woche ausgebucht."></textarea></label><button class="neos-button neos-button-danger" type="submit">Anfrage absagen</button></form>';
    }
}
