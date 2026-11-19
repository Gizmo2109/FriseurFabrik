<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Layout\FooterContact;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class FooterContactRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        return FooterContact::create(
            title: Editable::of($context, 'title'),
            salonName: $data->name,
            street: $data->street,
            zipCity: $data->zipCity(),
            phoneLabel: $data->phone,
            phoneHref: $data->phoneHref(),
            email: $data->email,
            emailHref: $data->emailHref(),
            mapsHref: $data->mapsUrl,
        );
    }
}
