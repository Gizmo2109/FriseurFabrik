<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Atom\NavItem;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/** A link in the header menu or a footer link list. Pages never decide themselves whether they are linked. */
final class MenuItemRenderer implements ContentNodeRendererInterface
{
    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $label = $context->node->getProperty('label');
        $link = $context->node->getProperty('link');
        $link = is_string($link) ? trim($link) : '';
        // tryResolveLinkUri() drops the fragment of node:// links (e.g. node://…#team)
        [$target, $fragment] = array_pad(explode('#', $link, 2), 2, '');
        $href = $target !== '' ? $context->neos->tryResolveLinkUri($target, $context->subgraph) : null;
        if ($href !== null && $fragment !== '') {
            $href = $href->withFragment($fragment);
        }

        $active = false;
        if ($fragment === '' && preg_match('#^node://([^/?]+)$#', $target, $matches)) {
            $activeIds = [$context->documentNode->aggregateId->value];
            foreach ($context->subgraph->findAncestorNodes($context->documentNode->aggregateId, FindAncestorNodesFilter::create()) as $ancestor) {
                $activeIds[] = $ancestor->aggregateId->value;
            }
            // the home page link is only active on the home page itself
            $active = $matches[1] === $context->documentNode->aggregateId->value
                || ($matches[1] !== $context->siteNode->aggregateId->value && in_array($matches[1], $activeIds, true));
        }
        $host = $href?->getHost() ?? '';
        $ownHost = RequestInformationHelper::generateBaseUri($context->request->getHttpRequest())->getHost();

        return NavItem::create(
            label: is_string($label) && $label !== '' ? $label : 'Menüpunkt',
            href: $href !== null ? (string)$href : '#',
            active: $active,
            external: $host !== '' && $host !== $ownHost,
        );
    }
}
