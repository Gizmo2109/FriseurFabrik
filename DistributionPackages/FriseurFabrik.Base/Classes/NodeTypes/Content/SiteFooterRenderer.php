<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Layout\SiteFooter;
use FriseurFabrik\Base\Rendering\Collections;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/** The footer shared by all pages: editable columns, legal links and copyright line */
final class SiteFooterRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly SalonContext $salon, private readonly Collections $collections)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $copyright = Editable::plain($context->node, 'copyright') === '' && !$context->renderingMode->isEdit
            ? '© ' . SalonContext::now()->format('Y') . ' ' . $this->salon->data($context)->name
            : Editable::of($context, 'copyright');

        return SiteFooter::create(
            columns: $this->collections->render($context, $context->node, 'columns', 'site-footer__grid'),
            legal: $this->collections->renderList($context, $context->node, 'legal', 'legal-nav'),
            copyright: $copyright,
        );
    }
}
