<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

/**
 * An immutable, type-safe collection of ContentStream objects
 *
 * @implements \IteratorAggregate<ContentStream>
 *
 * @api
 */
final class ContentStreams implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string,ContentStream>
     */
    private array $contentStreams;

    /**
     * @param iterable<ContentStream> $collection
     */
    private function __construct(iterable $collection)
    {
        $contentStreams = [];
        foreach ($collection as $item) {
            if (!$item instanceof ContentStream) {
                throw new \InvalidArgumentException(sprintf('ContentStreams must only consist of %s objects, got: %s', ContentStream::class, get_debug_type($item)), 1716900709);
            }
            $contentStreams[$item->id->value] = $item;
        }

        $this->contentStreams = $contentStreams;
    }

    /**
     * @param array<ContentStream> $contentStreams
     */
    public static function fromArray(array $contentStreams): self
    {
        return new self($contentStreams);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @return \Traversable<ContentStream>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->contentStreams);
    }

    /**
     * @param \Closure(ContentStream): bool $callback
     */
    public function filter(\Closure $callback): self
    {
        return new self(array_filter($this->contentStreams, $callback));
    }

    /**
     * @param \Closure(ContentStream): bool $callback
     */
    public function find(\Closure $callback): ?ContentStream
    {
        foreach ($this->contentStreams as $contentStream) {
            if ($callback($contentStream)) {
                return $contentStream;
            }
        }
        return null;
    }

    public function count(): int
    {
        return count($this->contentStreams);
    }

    public function isEmpty(): bool
    {
        return $this->contentStreams === [];
    }
}
