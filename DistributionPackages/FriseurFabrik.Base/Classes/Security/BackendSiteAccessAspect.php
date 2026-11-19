<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Security;

use FriseurFabrik\Base\Domain\Service\SalonSites;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Utility\ObjectAccess;

/**
 * Salon accounts may only open the backend of their own website(s). Editing other sites is denied by the
 * content repository anyway (Policy.Salons.yaml), this just avoids that they start editing at all.
 */
#[Flow\Aspect]
class BackendSiteAccessAspect
{
    #[Flow\Inject]
    protected SalonSites $salonSites;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    /**
     * The site list of the backend menu ("Inhalt") only shows the salon's own website(s).
     * Neos 9.2 does not check permissions there yet (see TODO in MenuHelper::buildSiteList()).
     *
     * @return list<array<string,mixed>>
     */
    #[Flow\Around('method(Neos\Neos\Controller\Backend\MenuHelper->buildSiteList())')]
    public function onlyOwnSitesInMenu(JoinPointInterface $joinPoint): array
    {
        $sites = $joinPoint->getAdviceChain()->proceed($joinPoint);
        if ($this->securityContext->hasRole('Neos.Neos:Administrator')) {
            return $sites;
        }
        $manageable = $this->salonSites->manageableSites();
        return $manageable === [] ? $sites : array_values(array_filter(
            $sites,
            static fn (array $site) => $site['nodeName'] instanceof SiteNodeName && isset($manageable[$site['nodeName']->value]),
        ));
    }

    #[Flow\Around('method(Neos\Neos\Ui\Controller\BackendController->indexAction())')]
    public function restrictToOwnSites(JoinPointInterface $joinPoint): mixed
    {
        if ($this->securityContext->hasRole('Neos.Neos:Administrator')) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
        $request = ObjectAccess::getProperty($joinPoint->getProxy(), 'request', true);
        assert($request instanceof ActionRequest);
        $siteNodeName = SiteDetectionResult::fromRequest($request->getHttpRequest())->siteNodeName->value;
        $manageable = $this->salonSites->manageableSites();
        // accounts without a salon role keep the default Neos behaviour
        if ($manageable === [] || isset($manageable[$siteNodeName])) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
        $ownSite = $this->salonSites->site((string)array_key_first($manageable));
        $ownBackend = $ownSite !== null ? $this->salonSites->baseUri($ownSite) . '/neos' : '/neos/logout';
        $name = htmlspecialchars((string)reset($manageable));
        return '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Kein Zugriff</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#222;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0}'
            . 'main{max-width:520px;padding:40px;background:#323232}a{color:#00adee}</style></head><body><main>'
            . '<h1>Kein Zugriff auf diese Website</h1>'
            . '<p>Ihr Konto darf nur die Website <strong>' . $name . '</strong> bearbeiten.</p>'
            . '<p><a href="' . htmlspecialchars($ownBackend) . '">Zur eigenen Website wechseln</a></p>'
            . '</main></body></html>';
    }
}
