<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\SlideItem;
use FriseurFabrik\Base\Components\Content\Slider;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class SliderRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $slides = [];
        foreach ((array)$context->node->getProperty('images') as $image) {
            if (Images::isImage($image) && ($img = $this->images->img($image, $context->request, 1600, 0.6))) {
                $slides[] = SlideItem::create(image: $img, caption: (string)$image->getCaption());
            }
        }
        return Sections::wrap($context, Slider::create(
            title: Editable::of($context, 'title'),
            slides: ComponentList::list(...$slides),
        ), 'slider');
    }
}
