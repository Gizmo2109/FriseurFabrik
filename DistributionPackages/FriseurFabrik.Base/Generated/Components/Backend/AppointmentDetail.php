<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Atom\FactRow;
use FriseurFabrik\Base\Components\Backend\HistoryEntry;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class AppointmentDetail implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<FactRow> $facts
     * @param _\ComponentListInterface<HistoryEntry> $history
     */
    private function __construct(
        private string $backHref,
        private string $name,
        private string $statusLabel,
        private string $statusClass,
        private string $headline,
        private _\ComponentListInterface $facts,
        private ?string $message,
        private ?_\ComponentInterface $actions,
        private ?_\ComponentInterface $contact,
        private ?_\ComponentInterface $note,
        private _\ComponentListInterface $history,
        private ?_\ComponentInterface $danger,
    ) {
    }

    /**
     * @param _\ComponentListInterface<FactRow> $facts
     * @param _\ComponentListInterface<HistoryEntry> $history
     */
    public static function create(
        string $backHref,
        string $name,
        string $statusLabel,
        string $statusClass,
        string $headline,
        _\ComponentListInterface $facts,
        ?string $message,
        _\ComponentInterface|string|null $actions,
        _\ComponentInterface|string|null $contact,
        _\ComponentInterface|string|null $note,
        _\ComponentListInterface $history,
        _\ComponentInterface|string|null $danger,
    ): self {
        return new self(
            backHref: $backHref,
            name: $name,
            statusLabel: $statusLabel,
            statusClass: $statusClass,
            headline: $headline,
            facts: $facts,
            message: $message,
            actions: is_string($actions) ? _\StringComponent::fromString($actions) : $actions,
            contact: is_string($contact) ? _\StringComponent::fromString($contact) : $contact,
            note: is_string($note) ? _\StringComponent::fromString($note) : $note,
            history: $history,
            danger: is_string($danger) ? _\StringComponent::fromString($danger) : $danger,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="ff-detail"><p><a class="ff-link" href="' . _\Util::escapeAttributeValue($this->backHref) . '">← Zurück zur Übersicht</a></p><header class="ff-detail__head"><h2 class="ff-detail__name">' . _\Util::escapeText($this->name) . '</h2><span class="' . _\Util::joinAttributeValues('ff-badge', _\Util::escapeAttributeValue($this->statusClass)) . '">' . _\Util::escapeText($this->statusLabel) . '</span></header><p class="ff-detail__headline">' . _\Util::escapeText($this->headline) . '</p><div class="ff-detail__grid"><div class="ff-detail__info"><div class="ff-panel"><h3 class="ff-panel__title">Anfrage</h3><dl class="ff-facts">' . $this->facts->render() . '</dl>' . (($this->message !== null) ? '<blockquote class="ff-message">' . (($this->message) !== null ? (((($temp = $this->message) === null) ? '' : _\Util::escapeText($temp))) : ('')) . '</blockquote>' : '') . '</div>' . ((($temp = $this->contact) === null) ? '' : $temp->render()) . ((($temp = $this->note) === null) ? '' : $temp->render()) . '<div class="ff-panel"><h3 class="ff-panel__title">Verlauf</h3><ul class="ff-history">' . $this->history->render() . '</ul></div>' . ((($temp = $this->danger) === null) ? '' : $temp->render()) . '</div><div class="ff-detail__actions">' . ((($temp = $this->actions) === null) ? '' : $temp->render()) . '</div></div></div>';
    }
}
