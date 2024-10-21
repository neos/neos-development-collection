<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\AssetUsage\Domain\AssetUsage;

/**
 * @implements \IteratorAggregate<AssetUsage>
 * @api
 */
#[Flow\Proxy(false)]
final class AssetUsages implements \IteratorAggregate, \Countable
{
    private ?int $countRuntimeCache = null;

    public function __construct(
        private readonly \Closure $generator,
        private readonly \Closure $counter,
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
        foreach ($this as $key => $usage) {
            yield $callback($usage, $key);
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
                    static function (\AppendIterator $globalAssetUsages, AssetUsages $assetUsage) {
                        $globalAssetUsages->append($assetUsage->getIterator());
                        return $globalAssetUsages;
                    },
                    new \AppendIterator()
                );
            },
            function () use ($assetUsages) {
                return array_reduce(
                    $assetUsages,
                    static fn($globalCount, AssetUsages $assetUsage) => $globalCount + $assetUsage->count(),
                    0
                );
            }
        );
    }
}
