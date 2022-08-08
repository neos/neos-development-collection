<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset;

use Neos\ContentRepository\Export\Asset\ValueObject\SerializedAsset;
use Neos\ContentRepository\Export\Asset\ValueObject\SerializedImageVariant;

interface AssetLoaderInterface
{
    public function findAssetById(string $assetId): SerializedAsset|SerializedImageVariant;
}
