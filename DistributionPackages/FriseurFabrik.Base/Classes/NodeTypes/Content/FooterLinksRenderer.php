<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Layout\FooterLinks;
use FriseurFabrik\Base\Rendering\Collections;
use FriseurFabrik\Base\Rendering\Editable;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class FooterLinksRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Collections $collections)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        return FooterLinks::create(
            title: Editable::of($context, 'title'),
            links: $this->collections->renderList($context, $context->node, 'links', 'footer-links'),
        );
    }
}
