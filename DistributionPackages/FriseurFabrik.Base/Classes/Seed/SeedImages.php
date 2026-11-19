<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Seed;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports the bundled seed images into the Neos media library (once; reused by sha1)
 */
#[Flow\Scope('singleton')]
class SeedImages
{
    #[Flow\Inject]
    protected PackageManager $packageManager;

    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    /** @var array<string,ImageInterface> */
    private array $imported = [];

    public function get(string $name): ImageInterface
    {
        if (isset($this->imported[$name])) {
            return $this->imported[$name];
        }
        $directory = $this->packageManager->getPackage('FriseurFabrik.Base')->getResourcesPath() . 'Private/SeedImages/';
        $path = $directory . $name . '.jpg';
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Seed image "%s" does not exist (%s)', $name, $path), 1794200001);
        }
        $existing = $this->assetRepository->findOneByResourceSha1(sha1_file($path));
        if ($existing instanceof ImageInterface) {
            return $this->imported[$name] = $existing;
        }
        $altTexts = Yaml::parseFile($directory . 'images.yaml');
        $image = new Image($this->resourceManager->importResource($path));
        $image->setTitle('Platzhalter: ' . $name);
        $image->setCaption((string)($altTexts[$name] ?? ''));
        $this->assetRepository->add($image);
        return $this->imported[$name] = $image;
    }
}
