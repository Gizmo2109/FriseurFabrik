<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\TeamMember;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class TeamMemberRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $name = Editable::plain($context->node, 'name');
        $initials = implode('', array_map(
            static fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)),
            array_slice(preg_split('/[\s-]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2),
        ));
        $instagram = $context->node->getProperty('instagramUrl');

        return TeamMember::create(
            photo: $this->images->img($context->node->getProperty('photo'), $context->request, 800, 1.25, $name),
            initials: $initials !== '' ? $initials : '·',
            name: Editable::of($context, 'name'),
            role: Editable::of($context, 'role'),
            bio: Editable::of($context, 'bio'),
            instagramHref: is_string($instagram) && preg_match('#^https?://#', $instagram) ? $instagram : null,
        );
    }
}
