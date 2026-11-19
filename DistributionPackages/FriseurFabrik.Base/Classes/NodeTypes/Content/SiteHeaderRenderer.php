<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Layout\SiteHeader;
use FriseurFabrik\Base\Rendering\Collections;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\SalonContext;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/** The header shared by all pages; its menu is a collection of menu items maintained by the editors */
final class SiteHeaderRenderer implements ContentNodeRendererInterface
{
    public function __construct(
        private readonly SalonContext $salon,
        private readonly Images $images,
        private readonly Collections $collections,
    ) {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        $showPhone = $context->node->getProperty('showPhone') !== false && $data->phoneHref() !== null;
        $showBooking = $context->node->getProperty('showBookingButton') !== false;

        return SiteHeader::create(
            homeHref: (string)$context->neos->getNodeUri($context->siteNode),
            salonName: $data->name,
            tagline: $data->tagline,
            logo: $this->images->img($data->logo, $context->request, 480, null, $data->name),
            menu: $this->collections->renderList($context, $context->node, 'menu', 'nav'),
            ctaLabel: $showBooking ? $data->bookingLabel : null,
            ctaHref: $showBooking ? $this->salon->bookingHref($context) : null,
            phoneLabel: $showPhone ? $data->phone : null,
            phoneHref: $showPhone ? $data->phoneHref() : null,
        );
    }
}
