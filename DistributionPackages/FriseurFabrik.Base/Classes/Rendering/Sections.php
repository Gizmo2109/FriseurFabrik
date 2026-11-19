<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use FriseurFabrik\Base\Components\Atom\Section;
use FriseurFabrik\Base\Components\Data\Tone;
use Neos\Flow\Annotations as Flow;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/** Wraps a content element into a page section using the node's tone and anchor (Mixin.Section) */
#[Flow\Proxy(false)]
final class Sections
{
    public static function wrap(NeosContext $context, ComponentInterface $content, string $modifier, ?string $defaultAnchor = null): ComponentInterface
    {
        $tone = $context->node->getProperty('tone');
        $anchor = $context->node->getProperty('sectionId');
        $anchor = is_string($anchor) && preg_match('/^[a-z0-9-]+$/', $anchor) ? $anchor : $defaultAnchor;

        return Section::create(
            tone: Tone::tryFrom(is_string($tone) ? $tone : '') ?? Tone::DEFAULT,
            anchor: $anchor,
            modifier: 'section--' . $modifier,
            content: $content,
        );
    }
}
