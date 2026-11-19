<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Seed;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesForName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use FriseurFabrik\Base\Security\SalonAccess;
use Neos\Neos\Aspects\SiteRepositoryCachingAspect;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Utility\ObjectAccess;

/**
 * Creates a complete salon site (site entity, domain, pages, content, images) from a salon data array
 */
#[Flow\Scope('singleton')]
class SalonSeeder
{
    private const PACKAGE = 'FriseurFabrik.Base';
    private const SITE_TYPE = 'FriseurFabrik.Base:Document.Salon';
    private const PAGE_TYPE = 'FriseurFabrik.Base:Document.Page';
    /** tethered child collections of container elements */
    private const COLLECTIONS = ['items', 'members', 'links'];

    #[Flow\Inject]
    protected SiteService $siteService;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected DomainRepository $domainRepository;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected SeedImages $seedImages;

    #[Flow\Inject]
    protected ObjectManagerInterface $objectManager;

    #[Flow\Inject]
    protected SalonAccess $salonAccess;

    private ContentRepository $contentRepository;

    /** @var array<string,NodeAggregateId> page key => id */
    private array $pageIds = [];

    /**
     * @param array<string,mixed> $data
     * @param int $port port of the local domain (dev server)
     */
    public function seed(array $data, bool $force, int $port, \Closure $log): Site
    {
        return $this->securityContext->withoutAuthorizationChecks(fn () => $this->doSeed($data, $force, $port, $log));
    }

    /** @param array<string,mixed> $data */
    private function doSeed(array $data, bool $force, int $port, \Closure $log): Site
    {
        $nodeName = $data['nodeName'];
        $existing = $this->siteRepository->findOneByNodeName($nodeName);
        if ($existing !== null) {
            if (!$force) {
                throw new \RuntimeException(sprintf('Site "%s" exists already, use --force to recreate it.', $nodeName), 1794200010);
            }
            $log(sprintf('Removing existing site "%s" …', $nodeName));
            $this->siteService->pruneSite($existing);
            $this->persistenceManager->persistAll();
            // Neos' SiteRepositoryCachingAspect memoizes findOneByNodeName() per process without invalidation,
            // createSite() would still see the pruned site -> reset that memo
            $cachingAspect = $this->objectManager->get(SiteRepositoryCachingAspect::class);
            ObjectAccess::setProperty($cachingAspect, 'byNodeNameCache', [], true);
            if ($this->siteRepository->findOneByNodeName($nodeName) !== null) {
                throw new \RuntimeException(sprintf('Site "%s" could not be removed.', $nodeName), 1794200015);
            }
        }

        // the site entity name only allows plain punctuation (Label validator), the salon name property keeps typography
        $siteName = strtr($data['site']['salonName'], ['’' => "'", '–' => '-', '—' => '-']);
        $site = $this->siteService->createSite(self::PACKAGE, $siteName, self::SITE_TYPE, $nodeName);
        $this->persistenceManager->persistAll();
        $this->contentRepository = $this->contentRepositoryRegistry->get($site->getConfiguration()->contentRepositoryId);
        $this->pageIds = [];

        $graph = $this->contentRepository->getContentGraph(WorkspaceName::forLive());
        $sitesId = $graph->findRootNodeAggregateByType(NodeTypeNameFactory::forSites())?->nodeAggregateId
            ?? throw new \RuntimeException('Sites root node missing', 1794200011);
        $siteId = $graph->findChildNodeAggregateByName($sitesId, NodeName::fromString($nodeName))?->nodeAggregateId
            ?? throw new \RuntimeException('Site node missing', 1794200012);
        $siteMainId = $graph->findChildNodeAggregateByName($siteId, NodeName::fromString('main'))?->nodeAggregateId
            ?? throw new \RuntimeException('Site main collection missing', 1794200013);

        $tree = SalonTemplate::build($data);

        // pages first, so content can link to them
        $this->pageIds['home'] = $siteId;
        foreach ($tree['pages'] as $page) {
            $this->pageIds[$page['key']] = NodeAggregateId::create();
        }
        $siteProperties = array_filter($data['site'], static fn ($value) => $value !== null && $value !== '');
        unset($siteProperties['parkingText']);
        $this->contentRepository->handle(SetNodeProperties::create(
            WorkspaceName::forLive(),
            $siteId,
            OriginDimensionSpacePoint::createWithoutDimensions(),
            PropertyValuesToWrite::fromArray($this->resolve($siteProperties + ['title' => $data['site']['salonName']])),
        ));

        foreach ($tree['pages'] as $page) {
            $mainId = NodeAggregateId::create();
            $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
                WorkspaceName::forLive(),
                $this->pageIds[$page['key']],
                NodeTypeName::fromString(self::PAGE_TYPE),
                OriginDimensionSpacePoint::createWithoutDimensions(),
                $siteId,
                null,
                PropertyValuesToWrite::fromArray($page['props']),
            )->withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths::fromArray(['main' => $mainId])));
            $this->createElements($mainId, $page['content']);
            $log(sprintf('  page /%s (%d elements)', $page['props']['uriPathSegment'], count($page['content'])));
        }
        $this->createElements($siteMainId, $tree['home']);
        $log(sprintf('  home (%d elements)', count($tree['home'])));

        // header and footer are tethered nodes of the site, shared by all pages
        foreach (['header' => $tree['header'], 'footer' => $tree['footer']] as $sharedName => $collections) {
            $sharedId = $graph->findChildNodeAggregateByName($siteId, NodeName::fromString($sharedName))?->nodeAggregateId
                ?? throw new \RuntimeException(sprintf('Site %s is missing', $sharedName), 1794200016);
            foreach ($collections as $collectionName => $elements) {
                $collectionId = $graph->findChildNodeAggregateByName($sharedId, NodeName::fromString($collectionName))?->nodeAggregateId
                    ?? throw new \RuntimeException(sprintf('Collection %s/%s is missing', $sharedName, $collectionName), 1794200017);
                $this->createElements($collectionId, $elements);
            }
        }
        $log('  header & footer');

        // access: only the salon's own role may edit the site; legal pages are locked for salon accounts
        $this->salonAccess->tag($siteId, SalonAccess::siteTag($nodeName), false);
        foreach ($tree['locked'] as $pageKey) {
            $this->salonAccess->tag($this->pageIds[$pageKey], SalonAccess::lockTag(), false);
        }
        $log(sprintf('  access tag %s, locked: %s', SalonAccess::siteTag($nodeName)->value, implode(', ', $tree['locked'])));

        $references = [];
        foreach ($tree['references'] as $name => $pageKey) {
            $references[] = NodeReferencesForName::fromTargets(ReferenceName::fromString($name), NodeAggregateIds::fromArray([$this->pageIds[$pageKey]]));
        }
        $this->contentRepository->handle(SetNodeReferences::create(
            WorkspaceName::forLive(),
            $siteId,
            OriginDimensionSpacePoint::createWithoutDimensions(),
            NodeReferencesToWrite::create(...$references),
        ));

        foreach ((array)($data['domains'] ?? []) as $index => $hostname) {
            $domain = new Domain();
            $domain->setHostname($hostname);
            $domain->setScheme('http');
            $domain->setPort($port);
            $domain->setSite($site);
            $this->domainRepository->add($domain);
            if ($index === 0) {
                $site->setPrimaryDomain($domain);
            }
        }
        $this->siteRepository->update($site);
        $this->persistenceManager->persistAll();

        return $site;
    }

    /** @param list<array<string,mixed>> $elements */
    private function createElements(NodeAggregateId $parentId, array $elements): void
    {
        foreach ($elements as $element) {
            $id = NodeAggregateId::create();
            $collectionIds = [];
            foreach (self::COLLECTIONS as $name) {
                if (isset($element['children'][$name])) {
                    $collectionIds[$name] = NodeAggregateId::create();
                }
            }
            $command = CreateNodeAggregateWithNode::create(
                WorkspaceName::forLive(),
                $id,
                NodeTypeName::fromString($element['type']),
                OriginDimensionSpacePoint::createWithoutDimensions(),
                $parentId,
                null,
                PropertyValuesToWrite::fromArray($this->resolve($element['props'])),
            );
            if ($collectionIds !== []) {
                $command = $command->withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths::fromArray($collectionIds));
            }
            $this->contentRepository->handle($command);
            foreach ($collectionIds as $name => $collectionId) {
                $this->createElements($collectionId, $element['children'][$name]);
            }
        }
    }

    /**
     * img:<name> -> seed image, page:<key> -> node:// link
     * @param array<string,mixed> $properties
     * @return array<string,mixed>
     */
    private function resolve(array $properties): array
    {
        $resolveValue = function (mixed $value): mixed {
            if (!is_string($value)) {
                return $value;
            }
            if (str_starts_with($value, 'img:')) {
                return $this->seedImages->get(substr($value, 4));
            }
            if (str_starts_with($value, 'page:')) {
                [$key, $fragment] = array_pad(explode('#', substr($value, 5), 2), 2, '');
                return 'node://' . ($this->pageIds[$key] ?? throw new \InvalidArgumentException('Unknown page ' . $key, 1794200014))->value
                    . ($fragment !== '' ? '#' . $fragment : '');
            }
            return $value;
        };
        return array_map(
            static fn (mixed $value) => is_array($value) ? array_map($resolveValue, $value) : $resolveValue($value),
            $properties,
        );
    }
}
