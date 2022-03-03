<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;
use Traversable;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<string, array<string>>
 */
final class AssetIdsByProperty implements \IteratorAggregate
{

    /**
     * @var array<string>
     */
    private array $propertyNamesWithoutAssets = [];
    /**
     * @var array<string, array<string>>
     */
    private array $assetIdentifiers = [];

    /**
     * @param array<string, array<string>> $assetIdentifiers
     */
    public function __construct(array $assetIdentifiers)
    {
        foreach ($assetIdentifiers as $propertyName => $assetIdentifiersForThisProperty) {
            $assetIdentifiersForThisProperty === [] ? $this->propertyNamesWithoutAssets[] = $propertyName : $this->assetIdentifiers[$propertyName] = $assetIdentifiersForThisProperty;
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
        return new \ArrayIterator($this->assetIdentifiers);
    }
}
