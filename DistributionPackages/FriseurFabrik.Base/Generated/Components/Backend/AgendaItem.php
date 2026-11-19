<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AgendaItem implements _\ComponentInterface
{
    private function __construct(
        private string $time,
        private string $name,
        private string $service,
        private string $stylist,
        private string $site,
        private string $href,
    ) {
    }

    public static function create(
        string $time,
        string $name,
        string $service,
        string $stylist,
        string $site,
        string $href,
    ): self {
        return new self(
            time: $time,
            name: $name,
            service: $service,
            stylist: $stylist,
            site: $site,
            href: $href,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<a class="ff-agenda__item" href="' . _\Util::escapeAttributeValue($this->href) . '"><span class="ff-agenda__time">' . _\Util::escapeText($this->time) . '</span><span class="ff-agenda__main"><strong>' . _\Util::escapeText($this->name) . '</strong><span class="ff-muted">' . _\Util::escapeText($this->service) . '</span></span><span class="ff-agenda__meta">' . _\Util::escapeText($this->stylist) . '<br /><span class="ff-muted">' . _\Util::escapeText($this->site) . '</span></span></a>';
    }
}
