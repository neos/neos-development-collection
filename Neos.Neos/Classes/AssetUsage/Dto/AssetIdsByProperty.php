<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;
use Traversable;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<string, array<AssetIdAndOriginalAssetId>>
 * @internal
 */
final class AssetIdsByProperty implements \IteratorAggregate
{
    /**
     * @param array<string, array<AssetIdAndOriginalAssetId>> $assetIds
     */
    public function __construct(
        private readonly array $assetIds,
    ) {
    }

    /**
     * @return array<string>
     */
    public function propertyNames(): array
    {
        return array_keys($this->assetIds);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->assetIds);
    }
}
