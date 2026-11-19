<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\PriceItem;
use FriseurFabrik\Base\Rendering\Editable;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class PriceItemRenderer implements ContentNodeRendererInterface
{
    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        return PriceItem::create(
            name: Editable::of($context, 'name'),
            description: Editable::of($context, 'description'),
            price: Editable::of($context, 'price'),
            duration: Editable::of($context, 'duration'),
        );
    }
}
