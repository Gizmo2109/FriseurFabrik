<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use FriseurFabrik\Base\Components\Atom\SelectOption;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class SelectGroup implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<SelectOption> $options
     */
    private function __construct(
        private string $label,
        private _\ComponentListInterface $options,
    ) {
    }

    /**
     * @param _\ComponentListInterface<SelectOption> $options
     */
    public static function create(
        string $label,
        _\ComponentListInterface $options,
    ): self {
        return new self(
            label: $label,
            options: $options,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<optgroup label="' . _\Util::escapeAttributeValue($this->label) . '">' . $this->options->render() . '</optgroup>';
    }
}
