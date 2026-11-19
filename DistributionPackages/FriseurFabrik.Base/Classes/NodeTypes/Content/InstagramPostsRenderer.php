<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\InstagramPosts;
use FriseurFabrik\Base\Rendering\Collections;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class InstagramPostsRenderer implements ContentNodeRendererInterface
{
    public function __construct(private readonly Collections $collections, private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        return Sections::wrap($context, InstagramPosts::create(
            title: Editable::of($context, 'title'),
            items: $this->collections->render($context, $context->node, 'items', 'insta-posts__grid'),
            profileHref: $data->instagramUrl,
            handle: $data->instagramHandle(),
        ), 'instagram-posts');
    }
}
