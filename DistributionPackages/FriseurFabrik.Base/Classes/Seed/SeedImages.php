<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Seed;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports the bundled seed images into the media collection of a salon (one copy per salon, so the media
 * libraries of the salons stay separate; reused when a site is seeded again)
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

    #[Flow\Inject]
    protected AssetCollectionRepository $assetCollectionRepository;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    /** @var array<string,ImageInterface> collection id/name => image */
    private array $imported = [];

    public function get(string $name, AssetCollection $collection): ImageInterface
    {
        $key = $this->persistenceManager->getIdentifierByObject($collection) . '/' . $name;
        if (isset($this->imported[$key])) {
            return $this->imported[$key];
        }
        $directory = $this->packageManager->getPackage('FriseurFabrik.Base')->getResourcesPath() . 'Private/SeedImages/';
        $path = $directory . $name . '.jpg';
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Seed image "%s" does not exist (%s)', $name, $path), 1794200001);
        }
        $sha1 = sha1_file($path);
        foreach ($collection->getAssets() as $asset) {
            if ($asset instanceof ImageInterface && $asset->getResource()->getSha1() === $sha1) {
                return $this->imported[$key] = $asset;
            }
        }
        $altTexts = Yaml::parseFile($directory . 'images.yaml');
        $image = new Image($this->resourceManager->importResource($path));
        $image->setTitle('Platzhalter: ' . $name);
        $image->setCaption((string)($altTexts[$name] ?? ''));
        $this->assetRepository->add($image);
        $collection->addAsset($image);
        $this->assetCollectionRepository->update($collection);
        return $this->imported[$key] = $image;
    }
}
