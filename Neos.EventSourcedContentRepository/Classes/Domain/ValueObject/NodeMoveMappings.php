<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class NodeMoveMappings implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var NodeMoveMapping[]
     */
    private $mappings;

    /**
     * @param NodeMoveMapping[] values
     */
    private function __construct(array $values)
    {
        $this->mappings = $values;
    }

    public static function fromArray(array $mappings): self
    {
        $processedMappings = [];
        foreach ($mappings as $mapping) {
            if (is_array($mapping)) {
                $processedMappings[] = NodeMoveMapping::fromArray($mapping);
            } elseif ($mapping instanceof NodeMoveMapping) {
                $processedMappings[] = $mapping;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid NodeMoveMapping. Expected instance of %s, got: %s',
                    NodeMoveMapping::class,
                    is_object($mapping) ? get_class($mapping) : gettype($mapping)
                ), 1547811318);
            }
        }
        return new static($processedMappings);
    }

    public static function createEmpty(): self
    {
        return new static([]);
    }

    public function appendMapping(NodeMoveMapping $mapping): self
    {
        $mappings = $this->mappings;
        $mappings[] = $mapping;
        return new static($mappings);
    }

    public function merge(NodeMoveMappings $other): self
    {
        return new static(array_merge($this->mappings, $other->mappings));
    }

    /**
     * @return NodeMoveMapping[]|\Traversable<NodeMoveMapping>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->mappings);
    }

    public function count(): int
    {
        return count($this->mappings);
    }

    public function jsonSerialize(): array
    {
        return $this->mappings;
    }
}
