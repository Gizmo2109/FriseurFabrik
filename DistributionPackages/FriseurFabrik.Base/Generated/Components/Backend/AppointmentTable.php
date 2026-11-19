<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\AppointmentRow;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AppointmentTable implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<AppointmentRow> $rows
     */
    private function __construct(
        private _\ComponentListInterface $rows,
        private bool $showSite,
        private string $emptyText,
    ) {
    }

    /**
     * @param _\ComponentListInterface<AppointmentRow> $rows
     */
    public static function create(
        _\ComponentListInterface $rows,
        bool $showSite,
        string $emptyText,
    ): self {
        return new self(
            rows: $rows,
            showSite: $showSite,
            emptyText: $emptyText,
        );
    }

    #[\Override]
    public function render(): string
    {
        return ((count($this->rows) > 0) ? '<table class="neos-table ff-table"><thead><tr><th>Kund:in</th>' . ($this->showSite ? '<th>Salon</th>' : '') . '<th>Leistung</th><th>Termin / Wunsch</th><th>Status</th><th>Eingang</th><th></th></tr></thead><tbody>' . $this->rows->render() . '</tbody></table>' : '<p class="ff-empty">' . _\Util::escapeText($this->emptyText) . '</p>');
    }
}
