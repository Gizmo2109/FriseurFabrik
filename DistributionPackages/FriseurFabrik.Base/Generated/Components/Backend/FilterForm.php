<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Option;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FilterForm implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<Option> $sites
     */
    private function __construct(
        private string $action,
        private string $view,
        private string $query,
        private _\ComponentListInterface $sites,
        private bool $showSites,
        private string $newHref,
    ) {
    }

    /**
     * @param _\ComponentListInterface<Option> $sites
     */
    public static function create(
        string $action,
        string $view,
        string $query,
        _\ComponentListInterface $sites,
        bool $showSites,
        string $newHref,
    ): self {
        return new self(
            action: $action,
            view: $view,
            query: $query,
            sites: $sites,
            showSites: $showSites,
            newHref: $newHref,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<form class="ff-filter" method="get" action="' . _\Util::escapeAttributeValue($this->action) . '"><input type="hidden" name="moduleArguments[@action]" value="index" /><input type="hidden" name="moduleArguments[view]" value="' . _\Util::escapeAttributeValue($this->view) . '" />' . ($this->showSites ? '<select name="moduleArguments[site]" aria-label="Salon" onchange="this.form.submit()"><option value="">Alle Salons</option>' . $this->sites->render() . '</select>' : '') . '<input type="search" name="moduleArguments[q]" value="' . _\Util::escapeAttributeValue($this->query) . '" placeholder="Name, Telefon, E-Mail" aria-label="Suchen" /><button class="neos-button" type="submit">Suchen</button><a class="neos-button neos-button-primary" href="' . _\Util::escapeAttributeValue($this->newHref) . '">+ Termin eintragen</a></form>';
    }
}
