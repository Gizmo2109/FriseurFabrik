<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\CallToAction;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class CallToActionRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $label = $context->node->getProperty('buttonLabel');
        $link = $context->node->getProperty('link');
        $href = is_string($link) && $link !== '' ? $context->neos->tryResolveLinkUri($link, $context->subgraph) : null;

        return Sections::wrap($context, CallToAction::create(
            title: Editable::of($context, 'title'),
            text: Editable::of($context, 'text'),
            buttonLabel: is_string($label) && $label !== '' ? $label : $this->salon->data($context)->bookingLabel,
            buttonHref: $href !== null ? (string)$href : $this->salon->bookingHref($context),
        ), 'cta');
    }
}
