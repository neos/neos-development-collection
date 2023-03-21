<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;
use Traversable;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<string, array<AssetIdAndOriginalAssetId>>
 */
final class AssetIdsByProperty implements \IteratorAggregate
{
    /**
     * @var array<string>
     */
    private array $propertyNamesWithoutAssets = [];

    /**
     * @var array<string, array<AssetIdAndOriginalAssetId>>
     */
    private array $assetIds = [];

    /**
     * @param array<string, array<AssetIdAndOriginalAssetId>> $assetIds
     */
    public function __construct(array $assetIds)
    {
        foreach ($assetIds as $propertyName => $assetIdsForThisProperty) {
            $assetIdsForThisProperty === []
                ? $this->propertyNamesWithoutAssets[] = $propertyName
                : $this->assetIds[$propertyName] = $assetIdsForThisProperty;
        }
    }

    /**
     * @return array<string>
     */
    public function propertyNamesWithoutAsset(): array
    {
        return $this->propertyNamesWithoutAssets;
    }

    public function hasPropertiesWithoutAssets(): bool
    {
        return $this->propertyNamesWithoutAssets !== [];
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->assetIds);
    }
}
