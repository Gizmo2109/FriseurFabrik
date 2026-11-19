<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\ImageCard;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class ImageCardRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $link = $context->node->getProperty('link');
        $href = is_string($link) && $link !== '' ? $context->neos->tryResolveLinkUri($link, $context->subgraph) : null;
        return ImageCard::create(
            image: $this->images->img($context->node->getProperty('image'), $context->request, 1200, 0.8, Editable::plain($context->node, 'title')),
            title: Editable::of($context, 'title'),
            text: Editable::of($context, 'text'),
            href: $href !== null ? (string)$href : null,
        );
    }
}
