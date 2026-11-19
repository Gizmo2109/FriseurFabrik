<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use FriseurFabrik\Base\Components\Atom\HoursRow;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FooterHours implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<HoursRow> $rows
     */
    private function __construct(
        private ?_\ComponentInterface $title,
        private _\ComponentListInterface $rows,
        private ?string $note,
    ) {
    }

    /**
     * @param _\ComponentListInterface<HoursRow> $rows
     */
    public static function create(
        _\ComponentInterface|string|null $title,
        _\ComponentListInterface $rows,
        ?string $note,
    ): self {
        return new self(
            title: is_string($title) ? _\StringComponent::fromString($title) : $title,
            rows: $rows,
            note: $note,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="footer-col"><p class="site-footer__heading">' . ((($temp = $this->title) === null) ? '' : $temp->render()) . '</p><dl class="hours hours--compact">' . $this->rows->render() . '</dl>' . (($this->note !== null) ? '<p class="site-footer__note">' . (($this->note) !== null ? (((($temp = $this->note) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</p>' : '') . '</div>';
    }
}
