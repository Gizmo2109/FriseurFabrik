<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\Gallery;
use FriseurFabrik\Base\Components\Content\GalleryItem;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class GalleryRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $items = [];
        foreach ((array)$context->node->getProperty('images') as $image) {
            if (!Images::isImage($image)) {
                continue;
            }
            $thumb = $this->images->img($image, $context->request, 800, 0.75);
            $large = $this->images->uri($image, $context->request, 2000);
            if ($thumb !== null && $large !== null) {
                $items[] = GalleryItem::create(image: $thumb, largeSrc: $large);
            }
        }

        return Sections::wrap($context, Gallery::create(
            title: Editable::of($context, 'title'),
            intro: Editable::of($context, 'intro'),
            items: ComponentList::list(...$items),
            mosaic: $context->node->getProperty('layout') === 'mosaic',
        ), 'gallery');
    }
}
