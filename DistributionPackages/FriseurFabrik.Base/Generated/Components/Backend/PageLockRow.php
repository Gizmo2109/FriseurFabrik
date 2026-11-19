<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\Csrf;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class PageLockRow implements _\ComponentInterface
{
    private function __construct(
        private string $title,
        private string $path,
        private string $depth,
        private bool $locked,
        private bool $inherited,
        private string $action,
        private string $nodeId,
        private Csrf $_23_12_Csrf,
    ) {
    }

    public static function create(
        string $title,
        string $path,
        string $depth,
        bool $locked,
        bool $inherited,
        string $action,
        string $csrf,
        string $nodeId,
    ): self {
        return new self(
            title: $title,
            path: $path,
            depth: $depth,
            locked: $locked,
            inherited: $inherited,
            action: $action,
            nodeId: $nodeId,
            _23_12_Csrf: Csrf::create(
                token: $csrf,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<tr><td style="padding-left:' . _\Util::escapeAttributeValue($this->depth) . 'px"><strong>' . _\Util::escapeText($this->title) . '</strong><br /><span class="ff-muted">' . _\Util::escapeText($this->path) . '</span></td><td>' . ($this->locked ? '<span class="ff-badge ff-badge--abgesagt">gesperrt</span>' : ($this->inherited ? '<span class="ff-badge ff-badge--vorgeschlagen">gesperrt (über übergeordnete Seite)</span>' : '<span class="ff-badge ff-badge--bestaetigt">bearbeitbar</span>')) . '</td><td class="ff-actions">' . ($this->inherited ? '' : '<form method="post" action="' . _\Util::escapeAttributeValue($this->action) . '">' . $this->_23_12_Csrf->render() . '<input type="hidden" name="moduleArguments[node]" value="' . _\Util::escapeAttributeValue($this->nodeId) . '" /><input type="hidden" name="moduleArguments[lock]"' . ($this->locked ? ' value="0"' : ' value="1"') . ' /><button class="' . _\Util::joinAttributeValues('neos-button', ($this->locked ? 'neos-button-success' : 'neos-button-danger')) . '" type="submit">' . ($this->locked ? 'Freigeben' : 'Sperren') . '</button></form>') . '</td></tr>';
    }
}
