<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class HistoryEntry implements _\ComponentInterface
{
    private function __construct(
        private string $at,
        private string $by,
        private string $text,
    ) {
    }

    public static function create(
        string $at,
        string $by,
        string $text,
    ): self {
        return new self(
            at: $at,
            by: $by,
            text: $text,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<li class="ff-history__entry"><span class="ff-muted">' . _\Util::escapeText($this->at) . ' · ' . _\Util::escapeText($this->by) . '</span><br />' . _\Util::escapeText($this->text) . '</li>';
    }
}
