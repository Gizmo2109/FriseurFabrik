<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Mail;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class MailFact implements _\ComponentInterface
{
    private function __construct(
        private string $label,
        private string $value,
    ) {
    }

    public static function create(
        string $label,
        string $value,
    ): self {
        return new self(
            label: $label,
            value: $value,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<tr><td style="padding:8px 12px 8px 0;color:#777777;font-size:14px;vertical-align:top;white-space:nowrap;">' . _\Util::escapeText($this->label) . '</td><td style="padding:8px 0;font-size:15px;font-weight:bold;color:#222222;">' . _\Util::escapeText($this->value) . '</td></tr>';
    }
}
