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

namespace Neos\ContentRepository\SharedModel\Node;

use Neos\ContentRepository\Projection\Content\Nodes;

/**
 * @implements \IteratorAggregate<string,NodeReference>
 */
final class NodeReferences implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<string,NodeReference>
     */
    private array $references;

    /**
     * @var \ArrayIterator<string,NodeReference>
     */
    protected \ArrayIterator $iterator;

    /**
     * @param array<string,NodeReference> $references
     */
    private function __construct(array $references)
    {
        $this->references = $references;
        $this->iterator = new \ArrayIterator($references);
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->references, $other->getReferences()));
    }

    /**
     * @return array<string,NodeReference>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @param array<string,array<string,mixed>|NodeReference> $nodeReferences
     */
    public static function fromArray(array $nodeReferences): self
    {
        $values = [];
        foreach ($nodeReferences as $nodeReferenceName => $nodeReferenceValue) {
            if (is_array($nodeReferenceValue)) {
                $values[$nodeReferenceName] = NodeReference::fromArray($nodeReferenceValue);
            } elseif ($nodeReferenceValue instanceof NodeReference) {
                $values[$nodeReferenceName] = $nodeReferenceValue;
            } else {
                /** @var mixed $nodeReferenceValue */
                throw new \InvalidArgumentException(sprintf(
                    'Invalid nodeReferences value. Expected instance of %s, got: %s',
                    NodeReference::class,
                    is_object($nodeReferenceValue) ? get_class($nodeReferenceValue) : gettype($nodeReferenceValue)
                ), 1546524480);
            }
        }

        return new self($values);
    }

    /**
     * @todo what is this supposed to do?
     */
    public static function fromNodes(Nodes $nodeReferences): self
    {
        $values = [];

        return new self($values);
    }

    /**
     * @return \ArrayIterator<string,NodeReference>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function count(): int
    {
        return count($this->references);
    }

    /**
     * @return array<string,NodeReference>
     */
    public function jsonSerialize(): array
    {
        return $this->references;
    }
}
