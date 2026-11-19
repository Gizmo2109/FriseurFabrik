<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use FriseurFabrik\Base\Components\Data\Img;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;

/**
 * Turns Neos media images into responsive Img structs (src + srcset) for the CPX components
 */
#[Flow\Scope('singleton')]
class Images
{
    private const WIDTHS = [480, 800, 1200, 1600, 2000];

    #[Flow\Inject]
    protected AssetService $assetService;

    /**
     * @param int $maxWidth widest rendition
     * @param float|null $ratio height/width; crops the image if set (e.g. 1.0 = square)
     */
    public function img(mixed $image, ActionRequest $request, int $maxWidth = 1600, ?float $ratio = null, ?string $alt = null): ?Img
    {
        if (!self::isImage($image)) {
            return null;
        }
        $candidates = [];
        $main = null;
        foreach (self::WIDTHS as $width) {
            if ($width > $maxWidth) {
                break;
            }
            $thumbnail = $this->thumbnail($image, $request, $width, $ratio);
            if ($thumbnail === null) {
                continue;
            }
            $candidates[(int)$thumbnail['width']] = $thumbnail['src'] . ' ' . (int)$thumbnail['width'] . 'w';
            $main = $thumbnail;
            if ((int)$thumbnail['width'] < $width) {
                // original is smaller than requested -> larger renditions would be identical
                break;
            }
        }
        if ($main === null) {
            return null;
        }
        $alt ??= (string)($image->getCaption() ?: $image->getTitle());

        return Img::create(
            src: (string)$main['src'],
            alt: $alt,
            srcset: implode(', ', $candidates),
            width: (int)$main['width'] ?: null,
            height: (int)$main['height'] ?: null,
        );
    }

    /** Single rendition, e.g. for og:image or Instagram tiles */
    public function uri(mixed $image, ActionRequest $request, int $width, ?float $ratio = null): ?string
    {
        return self::isImage($image) ? ($this->thumbnail($image, $request, $width, $ratio)['src'] ?? null) : null;
    }

    /** node properties hold Image or ImageVariant (both assets); array<Asset> properties may also contain non-images */
    public static function isImage(mixed $value): bool
    {
        return $value instanceof Asset && $value instanceof ImageInterface && $value->getResource() !== null;
    }

    /** @return array{src: string, width: int, height: int}|null */
    private function thumbnail(Asset&ImageInterface $image, ActionRequest $request, int $width, ?float $ratio): ?array
    {
        $configuration = $ratio === null
            ? new ThumbnailConfiguration(null, $width, null, null, false, false, true, 82)
            : new ThumbnailConfiguration($width, null, (int)round($width * $ratio), null, true, false, true, 82);
        $result = $this->assetService->getThumbnailUriAndSizeForAsset($image, $configuration, $request);
        if (!is_array($result) || !isset($result['src'])) {
            return null;
        }
        if ((int)($result['width'] ?? 0) > 0) {
            return ['src' => (string)$result['src'], 'width' => (int)$result['width'], 'height' => (int)($result['height'] ?? 0)];
        }
        // async thumbnails are not generated yet and report no size -> derive it from the original (no upscaling)
        $originalWidth = max(1, (int)$image->getWidth());
        $resultWidth = min($width, $originalWidth);
        $resultHeight = (int)round($resultWidth * ($ratio ?? ((int)$image->getHeight() / $originalWidth)));
        return ['src' => (string)$result['src'], 'width' => $resultWidth, 'height' => $resultHeight];
    }
}
