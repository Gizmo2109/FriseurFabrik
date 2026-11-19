<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AppointmentRow implements _\ComponentInterface
{
    private function __construct(
        private string $href,
        private string $created,
        private string $site,
        private string $name,
        private string $phone,
        private string $service,
        private string $when,
        private string $statusLabel,
        private string $statusClass,
        private bool $showSite,
    ) {
    }

    public static function create(
        string $href,
        string $created,
        string $site,
        string $name,
        string $phone,
        string $service,
        string $when,
        string $statusLabel,
        string $statusClass,
        bool $showSite,
    ): self {
        return new self(
            href: $href,
            created: $created,
            site: $site,
            name: $name,
            phone: $phone,
            service: $service,
            when: $when,
            statusLabel: $statusLabel,
            statusClass: $statusClass,
            showSite: $showSite,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<tr><td><a class="ff-link" href="' . _\Util::escapeAttributeValue($this->href) . '"><strong>' . _\Util::escapeText($this->name) . '</strong></a><br /><span class="ff-muted">' . _\Util::escapeText($this->phone) . '</span></td>' . ($this->showSite ? '<td>' . _\Util::escapeText($this->site) . '</td>' : '') . '<td>' . _\Util::escapeText($this->service) . '</td><td>' . _\Util::escapeText($this->when) . '</td><td><span class="' . _\Util::joinAttributeValues('ff-badge', _\Util::escapeAttributeValue($this->statusClass)) . '">' . _\Util::escapeText($this->statusLabel) . '</span></td><td class="ff-muted">' . _\Util::escapeText($this->created) . '</td><td class="ff-actions"><a class="neos-button neos-button-primary" href="' . _\Util::escapeAttributeValue($this->href) . '">Öffnen</a></td></tr>';
    }
}
