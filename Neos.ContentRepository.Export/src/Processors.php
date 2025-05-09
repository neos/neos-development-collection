<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export;

/**
 * @implements \IteratorAggregate<ProcessorInterface>
 */
final readonly class Processors implements \IteratorAggregate, \Countable
{
    /**
     * @param array<string, ProcessorInterface> $processors
     */
    private function __construct(
        private array $processors
    ) {
    }

    /**
     * @param array<string, ProcessorInterface> $processors
     */
    public static function fromArray(array $processors): self
    {
        return new self($processors);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->processors;
    }

    public function count(): int
    {
        return count($this->processors);
    }
}
