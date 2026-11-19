<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Media;

use FriseurFabrik\Base\Domain\Service\SalonSites;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\TypeHandling;

/**
 * Every salon site has its own media collection (Site → asset collection). Policy.Salons.yaml lets salon accounts
 * see only the assets of their own collection; website visitors see all.
 */
#[Flow\Scope('singleton')]
class SalonMedia
{
    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    #[Flow\Inject]
    protected AssetCollectionRepository $assetCollectionRepository;

    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected SalonSites $salonSites;

    /** the site's collection, created and assigned on first use */
    public function collection(Site $site): AssetCollection
    {
        $collection = $site->getAssetCollection();
        if ($collection === null) {
            $collection = new AssetCollection($site->getName());
            $this->assetCollectionRepository->add($collection);
            $site->setAssetCollection($collection);
            $this->siteRepository->update($site);
        }
        return $collection;
    }

    /**
     * Puts every asset the site uses (live workspace) into the site's collection: assets without collection are
     * adopted, assets of another collection (e.g. a placeholder image shared by several salons) are copied and the
     * node properties point to the copy afterwards. Idempotent.
     *
     * @return array{adopted: int, copied: int, skipped: int}
     */
    public function separate(Site $site): array
    {
        return $this->securityContext->withoutAuthorizationChecks(function () use ($site): array {
            $collection = $this->collection($site);
            $contentRepository = $this->contentRepositoryRegistry->get($site->getConfiguration()->contentRepositoryId);
            $siteNode = $this->salonSites->siteNode($site);
            // hidden nodes included
            $subgraph = $contentRepository->getContentGraph(WorkspaceName::forLive())
                ->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
            $stats = ['adopted' => 0, 'copied' => 0, 'skipped' => 0];
            $copies = [];
            $own = function (mixed $value) use ($collection, &$copies, &$stats): mixed {
                if (!$value instanceof Asset || $collection->getAssets()->contains($value)) {
                    return $value;
                }
                if ($value instanceof AssetVariantInterface) {
                    // shortcut: cropped variants of another salon's image are left as they are, copy them by hand if one ever shows up
                    $stats['skipped']++;
                    return $value;
                }
                if ($value->getAssetCollections()->isEmpty()) {
                    self::add($value, $collection);
                    $stats['adopted']++;
                    return $value;
                }
                $id = (string)$this->persistenceManager->getIdentifierByObject($value);
                if (!isset($copies[$id])) {
                    $copies[$id] = $this->copy($value, $collection);
                    $stats['copied']++;
                }
                return $copies[$id];
            };
            foreach ([$siteNode, ...$subgraph->findDescendantNodes($siteNode->aggregateId, FindDescendantNodesFilter::create())] as $node) {
                $changed = [];
                foreach ($node->properties as $name => $value) {
                    $mapped = is_array($value) ? array_map($own, $value) : $own($value);
                    if ($mapped !== $value) {
                        $changed[$name] = $mapped;
                    }
                }
                if ($changed !== []) {
                    $contentRepository->handle(SetNodeProperties::create(
                        WorkspaceName::forLive(),
                        $node->aggregateId,
                        $node->originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray($changed),
                    ));
                }
            }
            $this->assetCollectionRepository->update($collection);
            return $stats;
        });
    }

    /**
     * Slot for AssetService::assetCreated: uploads of salon accounts always end up in their salon's collection,
     * whatever collection is selected in the media browser (otherwise "all collections" would share them)
     */
    public function assignUploadToSalon(AssetInterface $asset): void
    {
        if (!$asset instanceof Asset || $this->securityContext->areAuthorizationChecksDisabled() || !$this->securityContext->canBeInitialized()
            || $this->securityContext->hasRole('Neos.Neos:Administrator')) {
            return;
        }
        $sites = $this->salonSites->manageableSites();
        $collection = count($sites) === 1 ? $this->salonSites->site((string)array_key_first($sites))?->getAssetCollection() : null;
        if ($collection !== null && self::add($asset, $collection)) {
            $this->assetCollectionRepository->update($collection);
        }
    }

    /**
     * AssetCollection::addAsset() only updates the owning side but checks the inverse side, so a second add in the
     * same request (e.g. Neos' own "upload to site collection" slot) would insert the asset twice -> keep both in sync
     */
    private static function add(Asset $asset, AssetCollection $collection): bool
    {
        if ($collection->getAssets()->contains($asset)) {
            return false;
        }
        $collection->getAssets()->add($asset);
        if (!$asset->getAssetCollections()->contains($collection)) {
            $asset->getAssetCollections()->add($collection);
        }
        return true;
    }

    private function copy(Asset $original, AssetCollection $collection): Asset
    {
        $source = $original->getResource();
        $resource = $this->resourceManager->importResource($source->getStream());
        $resource->setFilename($source->getFilename());
        $class = TypeHandling::getTypeForValue($original);
        /** @var Asset $copy Image, Document, Video and Audio all take the resource */
        $copy = new $class($resource);
        $copy->setTitle($original->getTitle());
        $copy->setCaption($original->getCaption());
        $copy->setCopyrightNotice($original->getCopyrightNotice());
        $this->assetRepository->add($copy);
        self::add($copy, $collection);
        return $copy;
    }
}
