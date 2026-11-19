<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Layout\FooterHours;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class FooterHoursRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        return FooterHours::create(
            title: Editable::of($context, 'title'),
            rows: $this->salon->hourRows($context),
            note: $this->salon->data($context)->hoursNote,
        );
    }
}
