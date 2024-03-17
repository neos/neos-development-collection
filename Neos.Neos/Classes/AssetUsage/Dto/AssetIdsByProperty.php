<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<string, array<AssetIdAndOriginalAssetId>>
 * @internal
 */
#[Flow\Proxy(false)]
final readonly class AssetIdsByProperty implements \IteratorAggregate
{
    /**
     * @param array<string, array<AssetIdAndOriginalAssetId>> $assetIds
     */
    public function __construct(
        private array $assetIds,
    ) {
    }

    /**
     * @return array<string>
     */
    public function propertyNames(): array
    {
        return array_keys($this->assetIds);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->assetIds;
    }
}
