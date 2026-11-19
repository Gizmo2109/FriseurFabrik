<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Security;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Psr\Log\LoggerInterface;

/**
 * Access rules are subtree tags in the live workspace:
 * - "salon-<site>" on each site node: only the role FriseurFabrik.Base:Salon_<site> (and admins) may edit it
 * - "fabrik-locked" on single pages: salon accounts may not edit them (see Policy.yaml)
 */
#[Flow\Scope('singleton')]
class SalonAccess
{
    public const LOCK_TAG = 'fabrik-locked';

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    public static function siteTag(string $siteNodeName): SubtreeTag
    {
        return SubtreeTag::fromString('salon-' . $siteNodeName);
    }

    public static function lockTag(): SubtreeTag
    {
        return SubtreeTag::fromString(self::LOCK_TAG);
    }

    public function tag(NodeAggregateId $nodeId, SubtreeTag $tag, bool $rebase = true): void
    {
        $this->securityContext->withoutAuthorizationChecks(fn () => $this->contentRepository()->handle(TagSubtree::create(
            WorkspaceName::forLive(),
            $nodeId,
            DimensionSpacePoint::createWithoutDimensions(),
            NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
            $tag,
        )));
        if ($rebase) {
            $this->rebaseEditorWorkspaces();
        }
    }

    public function untag(NodeAggregateId $nodeId, SubtreeTag $tag, bool $rebase = true): void
    {
        $this->securityContext->withoutAuthorizationChecks(fn () => $this->contentRepository()->handle(UntagSubtree::create(
            WorkspaceName::forLive(),
            $nodeId,
            DimensionSpacePoint::createWithoutDimensions(),
            NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
            $tag,
        )));
        if ($rebase) {
            $this->rebaseEditorWorkspaces();
        }
    }

    /** tags are set in live; personal workspaces must be rebased to see them */
    public function rebaseEditorWorkspaces(): void
    {
        try {
            // other users' workspaces: even administrators have no write permission on them
            $this->securityContext->withoutAuthorizationChecks(fn () => $this->contentRepositoryRegistry
                ->buildService(ContentRepositoryId::fromString('default'), new WorkspaceMaintenanceServiceFactory())
                ->rebaseOutdatedWorkspaces(RebaseErrorHandlingStrategy::STRATEGY_FAIL));
        } catch (\Exception $e) {
            // a workspace with conflicting unpublished changes stays outdated; Neos shows the editor a rebase hint
            $this->logger->warning('Workspaces could not be rebased after an access change: ' . $e->getMessage());
        }
    }

    private function contentRepository(): \Neos\ContentRepository\Core\ContentRepository
    {
        return $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));
    }
}
