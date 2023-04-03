<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<AssetUsages>
 * @api
 */
final class AssetUsagesByContentRepository implements \IteratorAggregate
{
    /**
     * @param array<AssetUsages> $assetUsages
     */
    public function __construct(
        private readonly array $assetUsages,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->assetUsages);
    }

    /**
     * @param \Closure $callback
     * @return \Traversable<mixed>
     */
    public function map(\Closure $callback): \Traversable
    {
        foreach ($this as $key => $usage) {
            yield $callback($usage, $key);
        }
    }
}
