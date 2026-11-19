<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Layout\FooterSocial;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class FooterSocialRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        $showBooking = $context->node->getProperty('showBookingButton') !== false;
        return FooterSocial::create(
            title: Editable::of($context, 'title'),
            social: $this->salon->socialLinks($context),
            ctaLabel: $showBooking ? $data->bookingLabel : null,
            ctaHref: $showBooking ? $this->salon->bookingHref($context) : null,
        );
    }
}
