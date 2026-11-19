<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\BeforeAfter;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class BeforeAfterRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        // both images are cropped to the same format so the comparison lines up
        return Sections::wrap($context, BeforeAfter::create(
            title: Editable::of($context, 'title'),
            text: Editable::of($context, 'text'),
            before: $this->images->img($context->node->getProperty('beforeImage'), $context->request, 1600, 0.75, 'Vorher'),
            after: $this->images->img($context->node->getProperty('afterImage'), $context->request, 1600, 0.75, 'Nachher'),
        ), 'before-after');
    }
}
