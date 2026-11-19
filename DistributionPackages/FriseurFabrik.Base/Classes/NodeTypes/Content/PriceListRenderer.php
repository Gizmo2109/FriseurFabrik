<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\PriceList;
use FriseurFabrik\Base\Rendering\Collections;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class PriceListRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Collections $collections)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        return Sections::wrap($context, PriceList::create(
            title: Editable::of($context, 'title'),
            note: Editable::of($context, 'note'),
            items: $this->collections->render($context, $context->node, 'items', 'price-list__items'),
        ), 'prices');
    }
}
