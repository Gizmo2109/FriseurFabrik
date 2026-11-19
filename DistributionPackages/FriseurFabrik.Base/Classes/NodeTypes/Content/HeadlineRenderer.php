<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\Headline;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class HeadlineRenderer implements ContentNodeRendererInterface
{
    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        return Sections::wrap($context, Headline::create(
            kicker: Editable::of($context, 'kicker'),
            title: Editable::of($context, 'title'),
            intro: Editable::of($context, 'intro'),
        ), 'headline');
    }
}
