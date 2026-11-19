<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\FeatureItem;
use FriseurFabrik\Base\Components\Data\IconName;
use FriseurFabrik\Base\Rendering\Editable;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class FeatureItemRenderer implements ContentNodeRendererInterface
{
    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $icon = $context->node->getProperty('icon');
        return FeatureItem::create(
            icon: IconName::tryFrom(is_string($icon) ? $icon : '') ?? IconName::SCISSORS,
            title: Editable::of($context, 'title'),
            text: Editable::of($context, 'text'),
        );
    }
}
