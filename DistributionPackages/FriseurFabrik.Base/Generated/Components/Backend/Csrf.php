<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Csrf implements _\ComponentInterface
{
    private function __construct(
        private string $token,
    ) {
    }

    public static function create(
        string $token,
    ): self {
        return new self(
            token: $token,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<input type="hidden" name="__csrfToken" value="' . _\Util::escapeAttributeValue($this->token) . '" />';
    }
}
