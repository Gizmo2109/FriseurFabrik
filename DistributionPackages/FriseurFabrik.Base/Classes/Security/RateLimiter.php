<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Security;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;

/**
 * Sliding window rate limit for the public forms (they trigger e-mails, so they must not be abusable)
 */
#[Flow\Scope('singleton')]
class RateLimiter
{
    #[Flow\Inject]
    protected CacheManager $cacheManager;

    /** @return bool true if the action is allowed (and counted) */
    public function attempt(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $cache = $this->cacheManager->getCache('FriseurFabrik_Base_RateLimit');
        assert($cache instanceof VariableFrontend);
        $entryId = sha1($key);
        $now = time();
        $hits = array_values(array_filter((array)($cache->get($entryId) ?: []), static fn ($at) => is_int($at) && $at > $now - $windowSeconds));
        if (count($hits) >= $maxAttempts) {
            return false;
        }
        $hits[] = $now;
        $cache->set($entryId, $hits, [], $windowSeconds);
        return true;
    }
}
