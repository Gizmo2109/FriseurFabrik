<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Content\Instagram;
use FriseurFabrik\Base\Components\Content\InstagramTile;
use FriseurFabrik\Base\Instagram\InstagramFeed;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\Images;
use FriseurFabrik\Base\Rendering\SalonContext;
use FriseurFabrik\Base\Rendering\Sections;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/** Latest posts via Instagram API if a token is configured on the site, otherwise manually chosen images */
final class InstagramRenderer implements ContentNodeRendererInterface
{
    public function __construct(
        private readonly SalonContext $salon,
        private readonly Images $images,
        private readonly InstagramFeed $feed,
    ) {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        $limit = max(1, min(24, (int)($context->node->getProperty('limit') ?: 8)));

        $tiles = [];
        foreach ($data->instagramAccessToken ? $this->feed->latest($data->instagramAccessToken, $limit) : [] as $post) {
            $tiles[] = InstagramTile::create(src: $post['src'], alt: $post['alt'], href: $post['href'], video: $post['video']);
        }
        if ($tiles === []) {
            foreach (array_slice((array)$context->node->getProperty('images'), 0, $limit) as $image) {
                if (Images::isImage($image) && ($src = $this->images->uri($image, $context->request, 600, 1.0))) {
                    $tiles[] = InstagramTile::create(src: $src, alt: (string)$image->getCaption(), href: $data->instagramUrl, video: false);
                }
            }
        }

        return Sections::wrap($context, Instagram::create(
            title: Editable::of($context, 'title'),
            handle: $data->instagramHandle(),
            profileHref: $data->instagramUrl,
            tiles: ComponentList::list(...$tiles),
        ), 'instagram');
    }
}
