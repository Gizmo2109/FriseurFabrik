<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Controller\Module;

use FriseurFabrik\Base\Components\Backend\ModuleTab;
use FriseurFabrik\Base\Components\Backend\PageLockList;
use FriseurFabrik\Base\Components\Backend\PageLockRow;
use FriseurFabrik\Base\Domain\Service\SalonSites;
use FriseurFabrik\Base\Security\SalonAccess;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use PackageFactory\ComponentEngine\ComponentList;

/**
 * Backend module "Administration → Seitenschutz" (administrators only): lock single pages for salon accounts
 */
class PageLocksController extends AbstractModuleController
{
    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected SalonSites $salonSites;

    #[Flow\Inject]
    protected SalonAccess $salonAccess;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    public function indexAction(string $site = ''): string
    {
        $sites = [];
        foreach ($this->siteRepository->findAll() as $candidate) {
            assert($candidate instanceof Site);
            $sites[$candidate->getNodeName()->value] = $candidate;
        }
        ksort($sites);
        $current = $sites[$site] ?? reset($sites) ?: null;
        $rows = [];
        if ($current instanceof Site) {
            $siteNode = $this->salonSites->siteNode($current);
            $subtree = $this->contentRepositoryRegistry->subgraphForNode($siteNode)->findSubtree(
                $siteNode->aggregateId,
                FindSubtreeFilter::create(nodeTypes: 'FriseurFabrik.Base:Document'),
            );
            $csrf = $this->securityContext->getCsrfProtectionToken();
            $action = $this->uriBuilder->reset()->uriFor('toggle', ['site' => $current->getNodeName()->value]);
            $walk = function (Subtree $subtree, string $path) use (&$walk, &$rows, $csrf, $action): void {
                $node = $subtree->node;
                $segment = (string)($node->getProperty('uriPathSegment') ?? '');
                $path = $subtree->level === 0 ? '/' : rtrim($path, '/') . '/' . $segment;
                $rows[] = PageLockRow::create(
                    title: (string)($node->getProperty('title') ?? $node->aggregateId->value),
                    path: $path,
                    depth: (string)(8 + 24 * $subtree->level),
                    locked: $node->tags->withoutInherited()->contain(SalonAccess::lockTag()),
                    inherited: $node->tags->onlyInherited()->contain(SalonAccess::lockTag()),
                    action: $action,
                    csrf: $csrf,
                    nodeId: $node->aggregateId->value,
                );
                foreach ($subtree->children as $child) {
                    $walk($child, $path);
                }
            };
            if ($subtree !== null) {
                $walk($subtree, '/');
            }
        }

        return PageLockList::create(
            sites: ComponentList::list(...array_map(fn (Site $candidate) => ModuleTab::create(
                label: $candidate->getName(),
                href: $this->uriBuilder->reset()->uriFor('index', ['site' => $candidate->getNodeName()->value]),
                count: '',
                active: $candidate === $current,
            ), array_values($sites))),
            rows: ComponentList::list(...$rows),
        )->render();
    }

    public function toggleAction(string $site, string $node, bool $lock = true): void
    {
        $siteEntity = $this->salonSites->site($site) ?? $this->throwStatus(404);
        $nodeId = NodeAggregateId::fromString($node);
        // only pages of the chosen site
        $page = $this->contentRepositoryRegistry->subgraphForNode($this->salonSites->siteNode($siteEntity))->findNodeById($nodeId) ?? $this->throwStatus(404);
        if ($lock) {
            $this->salonAccess->tag($page->aggregateId, SalonAccess::lockTag());
            $this->addFlashMessage('Die Seite „%s“ ist jetzt für Salon-Konten gesperrt.', 'Gesperrt', Message::SEVERITY_OK, [(string)$page->getProperty('title')]);
        } else {
            $this->salonAccess->untag($page->aggregateId, SalonAccess::lockTag());
            $this->addFlashMessage('Die Seite „%s“ kann wieder von Salon-Konten bearbeitet werden.', 'Freigegeben', Message::SEVERITY_OK, [(string)$page->getProperty('title')]);
        }
        $this->redirect('index', null, null, ['site' => $site]);
    }
}
