<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;
use Traversable;

/**
 * @Flow\Proxy(false)
 */
final class AssetIdsByProperty implements \IteratorAggregate
{

    private array $propertyNamesWithoutAssets = [];
    private array $assetIdentifiers = [];

    public function __construct(array $assetIdentifiers)
    {
        foreach ($assetIdentifiers as $propertyName => $assetIdentifiersForThisProperty) {
            $assetIdentifiersForThisProperty === [] ? $this->propertyNamesWithoutAssets[] = $propertyName : $this->assetIdentifiers[$propertyName] = $assetIdentifiersForThisProperty;
        }
    }

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
        return new \ArrayIterator($this->assetIdentifiers);
    }
}
