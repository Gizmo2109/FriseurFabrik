<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\ModuleTab;
use FriseurFabrik\Base\Components\Backend\PageLockRow;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class PageLockList implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<ModuleTab> $sites
     * @param _\ComponentListInterface<PageLockRow> $rows
     */
    private function __construct(
        private _\ComponentListInterface $sites,
        private _\ComponentListInterface $rows,
    ) {
    }

    /**
     * @param _\ComponentListInterface<ModuleTab> $sites
     * @param _\ComponentListInterface<PageLockRow> $rows
     */
    public static function create(
        _\ComponentListInterface $sites,
        _\ComponentListInterface $rows,
    ): self {
        return new self(
            sites: $sites,
            rows: $rows,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="ff-module"><p class="ff-muted">Gesperrte Seiten (inkl. Unterseiten und Inhalte) können Salon-Konten ansehen, aber nicht bearbeiten. Administratoren können alles bearbeiten.</p><div class="ff-module__bar"><nav class="ff-tabs" aria-label="Salons">' . $this->sites->render() . '</nav></div><table class="neos-table ff-table"><thead><tr><th>Seite</th><th>Zugriff für Salon-Konten</th><th></th></tr></thead><tbody>' . $this->rows->render() . '</tbody></table></div>';
    }
}
