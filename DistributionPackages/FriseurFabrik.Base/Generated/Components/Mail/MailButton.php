<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Mail;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class MailButton implements _\ComponentInterface
{
    private function __construct(
        private string $label,
        private string $href,
        private string $color,
    ) {
    }

    public static function create(
        string $label,
        string $href,
        string $color,
    ): self {
        return new self(
            label: $label,
            href: $href,
            color: $color,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<a href="' . _\Util::escapeAttributeValue($this->href) . '" style="display:inline-block;background:' . _\Util::escapeAttributeValue($this->color) . ';color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;font-size:15px;margin:0 8px 8px 0;">' . _\Util::escapeText($this->label) . '</a>';
    }
}
