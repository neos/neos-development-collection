<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class AssetUsages implements \IteratorAggregate, \Countable
{

    private ?int $countRuntimeCache = null;

    public function __construct(
        private \Closure $generator,
        private \Closure $counter
    ) {}

    /**
     * @return \Traversable<AssetUsage>|iterable<AssetUsage>
     * @noinspection PhpDocSignatureInspection
     */
    public function getIterator(): \Traversable
    {
        return ($this->generator)();
    }

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
}
