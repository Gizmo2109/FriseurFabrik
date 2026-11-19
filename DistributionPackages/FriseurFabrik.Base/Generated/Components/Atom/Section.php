<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Atom;

use FriseurFabrik\Base\Components\Data\Tone;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Section implements _\ComponentInterface
{
    private function __construct(
        private Tone $tone,
        private ?string $anchor,
        private string $modifier,
        private ?_\ComponentInterface $content,
    ) {
    }

    public static function create(
        Tone $tone,
        ?string $anchor,
        string $modifier,
        _\ComponentInterface|string|null $content,
    ): self {
        return new self(
            tone: $tone,
            anchor: $anchor,
            modifier: $modifier,
            content: is_string($content) ? _\StringComponent::fromString($content) : $content,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<section class="' . _\Util::joinAttributeValues('section', 'section--' . $this->tone->asAttributeValue(), _\Util::escapeAttributeValue($this->modifier)) . '"' . ((($temp = $this->anchor) === null) ? '' : ' id="' . _\Util::escapeAttributeValue($temp) . '"') . '><div class="container">' . ((($temp = $this->content) === null) ? '' : $temp->render()) . '</div></section>';
    }
}
