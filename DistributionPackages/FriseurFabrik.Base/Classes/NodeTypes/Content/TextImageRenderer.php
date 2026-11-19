<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\TextImage;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class TextImageRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        return Sections::wrap($context, TextImage::create(
            kicker: Editable::of($context, 'kicker'),
            title: Editable::of($context, 'title'),
            text: Editable::of($context, 'text', true),
            image: $this->images->img($context->node->getProperty('image'), $context->request, 1200, 0.8),
            imageLeft: $context->node->getProperty('imagePosition') === 'left',
        ), 'text-image');
    }
}
