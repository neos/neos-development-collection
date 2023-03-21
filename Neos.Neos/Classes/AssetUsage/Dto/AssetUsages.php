<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<AssetUsage>
 */
final class AssetUsages implements \IteratorAggregate, \Countable
{
    private ?int $countRuntimeCache = null;

    public function __construct(
        private \Closure $generator,
        private \Closure $counter,
    ) {
    }

    /**
     * @return \Iterator<AssetUsage>|AssetUsage[]
     */
    public function getIterator(): \Iterator
    {
        return ($this->generator)();
    }

    /**
     * @param \Closure $callback
     * @return \Traversable<mixed>
     */
    public function map(\Closure $callback): \Traversable
    {
        foreach ($this as $usage) {
            yield $callback($usage);
        }
    }

    public function count(): int
    {
        if ($this->countRuntimeCache === null) {
            $this->countRuntimeCache = ($this->counter)();
        }
        return $this->countRuntimeCache;
    }

    /**
     * @param array<AssetUsages> $assetUsages
     */
    public static function fromArrayOfAssetUsages(array $assetUsages): self
    {
        return new self(
            function () use ($assetUsages) {
                return array_reduce(
                    $assetUsages,
                    function (\AppendIterator $globalAssetUsages, AssetUsages $assetUsage) {
                        $globalAssetUsages->append($assetUsage->getIterator());
                        return $globalAssetUsages;
                    },
                    new \AppendIterator()
                );
            },
            function () use ($assetUsages) {
                return array_reduce(
                    $assetUsages,
                    fn($globalCount, AssetUsages $assetUsage) => $globalCount + $assetUsage->count(),
                    0
                );
            }
        );
    }
}
