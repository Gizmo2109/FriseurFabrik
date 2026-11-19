<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\InstagramPost;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

final class InstagramPostRenderer implements ContentNodeRendererInterface
{
    private const POST_URL = '#^https://(www\.)?instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)#';

    public function __construct(private readonly Images $images)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $url = $context->node->getProperty('postUrl');
        // normalized permalink; only real post URLs reach the page (and later Instagram's embed script)
        $postUrl = is_string($url) && preg_match(self::POST_URL, trim($url), $m) ? 'https://www.instagram.com/' . $m[2] . '/' . $m[3] . '/' : null;
        return InstagramPost::create(
            postUrl: $postUrl,
            image: $this->images->img($context->node->getProperty('image'), $context->request, 800, 1.0, 'Instagram-Beitrag'),
            caption: Editable::of($context, 'caption'),
        );
    }
}
