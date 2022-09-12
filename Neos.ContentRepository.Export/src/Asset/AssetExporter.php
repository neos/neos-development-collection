<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;

final class AssetExporter
{
    private array $exportedAssetIds = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly AssetLoaderInterface $assetLoader,
        private readonly ResourceLoaderInterface $resourceLoader,
    ) {}

    public function exportAsset(string $assetId): void
    {
        if (array_key_exists($assetId, $this->exportedAssetIds)) {
            return;
        }
        $serializedAsset = $this->assetLoader->findAssetById($assetId);
        $this->exportedAssetIds[$assetId] = true;
        if ($serializedAsset instanceof SerializedImageVariant) {
            $this->files->write('ImageVariants/' . $serializedAsset->identifier . '.json', $serializedAsset->toJson());
            $this->exportAsset($serializedAsset->originalAssetIdentifier);
            return;
        }
        try {
            $resourceStream = $this->resourceLoader->getStreamBySha1($serializedAsset->resource->sha1);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to find resource with SHA1 "%s", referenced in asset "%s": %s', $serializedAsset->resource->sha1, $serializedAsset->identifier, $e->getMessage()), 1658495163, $e);
        }
        $this->files->write('Assets/' . $serializedAsset->identifier . '.json', $serializedAsset->toJson());
        $this->files->writeStream('Resources/' . $serializedAsset->resource->sha1, $resourceStream);
    }
}
