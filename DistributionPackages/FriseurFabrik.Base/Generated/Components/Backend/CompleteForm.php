<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class CompleteForm implements _\ComponentInterface
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
        return '<form class="ff-panel ff-form" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '">' . $this->_7_4_Csrf->render() . '<h3 class="ff-panel__title">Nach dem Termin</h3><p class="ff-contact-buttons"><button class="neos-button neos-button-success" type="submit" name="moduleArguments[result]" value="done">Erledigt</button><button class="neos-button" type="submit" name="moduleArguments[result]" value="noshow">Nicht erschienen</button></p></form>';
    }
}
