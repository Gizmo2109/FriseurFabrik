<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class DeleteForm implements _\ComponentInterface
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
        return '<form class="ff-delete" method="post" action="' . _\Util::escapeAttributeValue($this->action) . '" data-confirm="Anfrage endgültig löschen? Das kann nicht rückgängig gemacht werden.">' . $this->_7_4_Csrf->render() . '<button class="neos-button neos-button-danger" type="submit">Anfrage löschen (DSGVO)</button></form>';
    }
}
