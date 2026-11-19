<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\Contact;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class ContactRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        return Sections::wrap($context, Contact::create(
            title: Editable::of($context, 'title'),
            text: Editable::of($context, 'text', true),
            salonName: $data->name,
            street: $data->street,
            zipCity: trim($data->zipCity() . ($data->district ? ' (' . $data->district . ')' : '')),
            phoneLabel: $data->phone,
            phoneHref: $data->phoneHref(),
            email: $data->email,
            emailHref: $data->emailHref(),
            whatsappHref: $data->whatsappHref(),
            mapsHref: $data->mapsUrl,
            parkingInfo: $data->parkingInfo,
            hours: $this->salon->hourRows($context),
            hoursNote: $data->hoursNote,
        ), 'contact', 'kontakt');
    }
}
