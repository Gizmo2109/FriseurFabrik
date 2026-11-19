<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\AgendaItem;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AgendaDay implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<AgendaItem> $items
     */
    private function __construct(
        private string $label,
        private _\ComponentListInterface $items,
    ) {
    }

    /**
     * @param _\ComponentListInterface<AgendaItem> $items
     */
    public static function create(
        string $label,
        _\ComponentListInterface $items,
    ): self {
        return new self(
            label: $label,
            items: $items,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<section class="ff-agenda__day"><h3 class="ff-agenda__date">' . _\Util::escapeText($this->label) . '</h3>' . $this->items->render() . '</section>';
    }
}
