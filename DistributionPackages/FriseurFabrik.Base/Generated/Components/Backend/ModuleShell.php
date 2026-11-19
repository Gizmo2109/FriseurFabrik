<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Backend;

use FriseurFabrik\Base\Components\Backend\ModuleTab;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class ModuleShell implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<ModuleTab> $tabs
     */
    private function __construct(
        private _\ComponentListInterface $tabs,
        private ?_\ComponentInterface $tools,
        private ?_\ComponentInterface $content,
    ) {
    }

    /**
     * @param _\ComponentListInterface<ModuleTab> $tabs
     */
    public static function create(
        _\ComponentListInterface $tabs,
        _\ComponentInterface|string|null $tools,
        _\ComponentInterface|string|null $content,
    ): self {
        return new self(
            tabs: $tabs,
            tools: is_string($tools) ? _\StringComponent::fromString($tools) : $tools,
            content: is_string($content) ? _\StringComponent::fromString($content) : $content,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="ff-module"><div class="ff-module__bar"><nav class="ff-tabs" aria-label="Ansichten">' . $this->tabs->render() . '</nav><div class="ff-module__tools">' . ((($temp = $this->tools) === null) ? '' : $temp->render()) . '</div></div>' . ((($temp = $this->content) === null) ? '' : $temp->render()) . '</div>';
    }
}
