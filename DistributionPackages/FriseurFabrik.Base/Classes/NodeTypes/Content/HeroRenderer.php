<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\Hero;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\SalonContext;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class HeroRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images, private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $node = $context->node;
        $data = $this->salon->data($context);
        $showBooking = $node->getProperty('showBookingButton') !== false;
        $showPhone = $node->getProperty('showPhoneButton') !== false && $data->phoneHref() !== null;

        return Hero::create(
            kicker: Editable::of($context, 'kicker'),
            headline: Editable::of($context, 'headline'),
            text: Editable::of($context, 'text'),
            image: $this->images->img($node->getProperty('image'), $context->request, 2000, null, ''),
            large: $node->getProperty('size') !== 'small',
            bookingLabel: $showBooking ? $data->bookingLabel : null,
            bookingHref: $showBooking ? $this->salon->bookingHref($context) : null,
            phoneLabel: $showPhone ? $data->phone : null,
            phoneHref: $showPhone ? $data->phoneHref() : null,
        );
    }
}
