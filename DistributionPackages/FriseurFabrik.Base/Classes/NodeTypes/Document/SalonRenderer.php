<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Document;

use FriseurFabrik\Base\Rendering\PageLayout;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\DocumentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/** Renderer for FriseurFabrik.Base:Document.Salon (the home page / site node) */
final class SalonRenderer implements DocumentNodeRendererInterface
{
    public function __construct(private readonly PageLayout $layout)
    {
    }

    public function renderAsDocument(NeosContext $context): ComponentInterface
    {
        return $this->layout->render($context);
    }
}
