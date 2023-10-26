<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<AssetUsages>
 * @api
 */
#[Flow\Proxy(false)]
final readonly class AssetUsagesByContentRepository implements \IteratorAggregate, \Countable
{
    /**
     * @param array<AssetUsages> $assetUsages
     */
    public function __construct(
        private array $assetUsages,
    ) {
    }

    public function getIterator(): \Traversable
    {
        yield from $this->assetUsages;
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

    public function count(): int
    {
        return (int)array_sum(array_map(static fn (AssetUsages $assetUsages) => $assetUsages->count(), $this->assetUsages));
    }
}
