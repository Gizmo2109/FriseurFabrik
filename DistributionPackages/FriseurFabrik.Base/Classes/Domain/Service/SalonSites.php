<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Domain\Service;

use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonData;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Access to the salon sites outside of a node rendering request (e-mails, backend module, response pages)
 */
#[Flow\Scope('singleton')]
class SalonSites
{
    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected SiteNodeUtility $siteNodeUtility;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected ContentRepositoryAuthorizationService $authorizationService;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function site(string $siteNodeName): ?Site
    {
        return preg_match('/^[a-z0-9-]+$/', $siteNodeName) ? $this->siteRepository->findOneByNodeName($siteNodeName) : null;
    }

    public function siteNode(Site $site): Node
    {
        return $this->siteNodeUtility->findSiteNodeBySite($site, WorkspaceName::forLive(), DimensionSpacePoint::createWithoutDimensions());
    }

    public function salonData(Site $site): SalonData
    {
        return SalonData::fromSiteNode($this->siteNode($site));
    }

    /** public address of the site, used for links in e-mails */
    public function baseUri(Site $site, ?ActionRequest $request = null): string
    {
        $domain = $site->getPrimaryDomain();
        if ($domain !== null) {
            return rtrim((string)$domain, '/');
        }
        return $request !== null ? rtrim((string)RequestInformationHelper::generateBaseUri($request->getHttpRequest()), '/') : '';
    }

    /**
     * Sites whose appointments the current backend user may manage: those whose site node the user may edit
     * (Neos administrators: all). Edit rights per salon are granted by the generated Policy.Salons.yaml.
     *
     * @return array<string,string> site node name => salon name
     */
    public function manageableSites(): array
    {
        $roles = $this->securityContext->getRoles();
        $sites = [];
        foreach ($this->siteRepository->findAll() as $site) {
            assert($site instanceof Site);
            try {
                $siteNode = $this->siteNode($site);
            } catch (\RuntimeException) {
                continue;
            }
            if ($this->authorizationService->getNodePermissions($siteNode, $roles)->edit) {
                $sites[$site->getNodeName()->value] = SalonData::fromSiteNode($siteNode)->name;
            }
        }
        asort($sites);
        return $sites;
    }

    /** @return list<string> names of the team members that can be booked */
    public function stylists(Site $site): array
    {
        $siteNode = $this->siteNode($site);
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($siteNode);
        $names = [];
        foreach ($subgraph->findDescendantNodes($siteNode->aggregateId, FindDescendantNodesFilter::create(nodeTypes: 'FriseurFabrik.Base:Content.TeamMember')) as $member) {
            $name = Editable::plain($member, 'name');
            if ($name !== '' && $member->getProperty('bookable') !== false) {
                $names[$name] = $name;
            }
        }
        return array_values($names);
    }

    public function mayManage(string $siteNodeName): bool
    {
        return array_key_exists($siteNodeName, $this->manageableSites());
    }
}
