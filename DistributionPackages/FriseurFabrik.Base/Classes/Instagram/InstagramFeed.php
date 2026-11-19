<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Instagram;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Fetches the latest posts of an Instagram professional account via the Instagram API (Instagram Login).
 * Results are cached for an hour; failures degrade to an empty list so the page still renders.
 */
#[Flow\Scope('singleton')]
class InstagramFeed
{
    private const ENDPOINT = 'https://graph.instagram.com/me/media';

    #[Flow\Inject]
    protected CacheManager $cacheManager;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    /**
     * @return list<array{src: string, href: ?string, alt: string, video: bool}>
     */
    public function latest(string $accessToken, int $limit): array
    {
        $cache = $this->cacheManager->getCache('FriseurFabrik_Base_Instagram');
        assert($cache instanceof VariableFrontend);
        $key = sha1($accessToken) . '_' . $limit;
        $cached = $cache->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $posts = [];
        try {
            $url = self::ENDPOINT . '?' . http_build_query([
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink',
                'limit' => $limit,
                'access_token' => $accessToken,
            ]);
            $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]));
            $data = json_decode($response ?: '', true);
            foreach (is_array($data['data'] ?? null) ? $data['data'] : [] as $item) {
                $isVideo = ($item['media_type'] ?? '') === 'VIDEO';
                $src = $isVideo ? ($item['thumbnail_url'] ?? null) : ($item['media_url'] ?? null);
                if (!is_string($src) || !str_starts_with($src, 'https://')) {
                    continue;
                }
                $permalink = $item['permalink'] ?? null;
                $posts[] = [
                    'src' => $src,
                    'href' => is_string($permalink) && str_starts_with($permalink, 'https://') ? $permalink : null,
                    'alt' => mb_substr(trim((string)($item['caption'] ?? 'Instagram-Beitrag')), 0, 120),
                    'video' => $isVideo,
                ];
            }
            if ($posts === [] && isset($data['error'])) {
                $this->logger->warning('Instagram feed could not be loaded: ' . json_encode($data['error']));
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Instagram feed could not be loaded: ' . $e->getMessage());
        }
        // retry failures sooner than successful fetches
        $cache->set($key, $posts, [], $posts !== [] ? 3600 : 300);
        return $posts;
    }
}
