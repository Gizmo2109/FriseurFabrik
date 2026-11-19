<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\NodeMapping\NodeMapperInterface;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\ComponentEngine\Domain\Markup\Html5TagName;
use PackageFactory\ComponentEngine\StringComponent;
use PackageFactory\Neos\ComponentEngine\Integration\ContentRenderer;
use PackageFactory\Neos\ComponentEngine\Integration\RenderingEntryPoint;
use PackageFactory\Neos\ComponentEngine\Integration\RenderingUseCase;
use PackageFactory\Neos\ComponentEngine\NeosContext;
use PackageFactory\Neos\ComponentEngine\Presentation\Component\ContentElementList;

/**
 * Renders a tethered content collection with the same markup as ContentRenderer::forContentCollectionChildNode(),
 * but without its (always on) component cache: our elements show site wide data (hours, links, price lists),
 * which that cache would not invalidate.
 * shortcut: no output caching at all; fine for small salon sites, add #[Cached] entry points if traffic grows.
 */
#[Flow\Scope('singleton')]
class Collections
{
    public function __construct(
        private readonly ContentRenderer $contentRenderer,
        private readonly NodeMapperInterface $nodeMapper,
    ) {
    }

    public function render(NeosContext $context, Node $parent, string $collectionName, string ...$classes): ComponentInterface
    {
        return $this->renderAs('div', $context, $parent, $collectionName, ...$classes);
    }

    /** collection rendered as <ul>, for menus whose items render <li> */
    public function renderList(NeosContext $context, Node $parent, string $collectionName, string ...$classes): ComponentInterface
    {
        return $this->renderAs('ul', $context, $parent, $collectionName, ...$classes);
    }

    private function renderAs(string $tagName, NeosContext $context, Node $parent, string $collectionName, string ...$classes): ComponentInterface
    {
        $collection = $context->subgraph->findNodeByPath(NodeName::fromString($collectionName), $parent->aggregateId);
        if ($collection === null) {
            return StringComponent::fromString('');
        }
        $children = $context->subgraph->findChildNodes($collection->aggregateId, FindChildNodesFilter::create());

        return ContentElementList::create(
            editable: $context->renderingMode->isEdit,
            nodeAddress: NodeAddress::fromNode($collection),
            fusionPath: RenderingEntryPoint::forUseCase(RenderingUseCase::CONTENT_COLLECTION)->serializeToString(),
            content: ComponentList::list(...$children->map(
                fn (Node $child) => $this->contentRenderer->render(
                    $context->with(current: $this->nodeMapper->map($child, $context->subgraph)),
                    RenderingUseCase::CONTENT,
                ),
            )),
            tagName: Html5TagName::from($tagName),
            additionalClasses: $classes,
        );
    }
}
