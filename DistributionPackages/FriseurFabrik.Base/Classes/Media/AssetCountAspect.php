<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Media;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * AssetRepository::countAll() is a native SQL query, which ignores the media privileges: the media module of a salon
 * account would show "Alle 142" (the assets of all salons) while listing only its own. Count via DQL instead.
 */
#[Flow\Aspect]
class AssetCountAspect
{
    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Around('method(Neos\Media\Domain\Repository\AssetRepository->countAll())')]
    public function countOnlyVisibleAssets(JoinPointInterface $joinPoint): int
    {
        $repository = $joinPoint->getProxy();
        if (!$repository instanceof AssetRepository || !$this->securityContext->canBeInitialized()
            || $this->securityContext->areAuthorizationChecksDisabled() || $this->securityContext->hasRole('Neos.Neos:Administrator')) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
        return $repository->findAll()->count();
    }
}
