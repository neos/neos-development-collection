<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\Adapters;

use Neos\ContentRepository\Export\Asset\AssetLoaderInterface;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;

final class AssetRepositoryAssetLoader implements AssetLoaderInterface
{
    public function __construct(
        private readonly AssetRepository $assetRepository,
    ) {}

    public function findAssetById(string $assetId): SerializedAsset|SerializedImageVariant
    {
        $asset = $this->assetRepository->findByIdentifier($assetId);
        if ($asset === null) {
            throw new \InvalidArgumentException(sprintf('Failed to load asset with id "%s"', $assetId), 1658652322);
        }
        if ($asset instanceof ImageVariant) {
            return SerializedImageVariant::fromImageVariant($asset);
        }
        if (!$asset instanceof Asset) {
            throw new \RuntimeException(sprintf('Asset "%s" was expected to be of type "%s" bit it is a "%s"', $assetId, Asset::class, get_debug_type($asset)), 1658652326);
        }
        return SerializedAsset::fromAsset($asset);
    }
}
