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

namespace Neos\ContentRepository\Core\Feature\NodeDuplication\Dto;

use Neos\ContentRepository\Core\Projection\ContentGraph\References;

/**
 * @implements \IteratorAggregate<string,NodeReferenceSnapshot>
 * @internal not yet finished
 */
final class NodeReferencesSnapshot implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<string,NodeReferenceSnapshot>
     */
    private array $references;

    /**
     * @var \ArrayIterator<string,NodeReferenceSnapshot>
     */
    protected \ArrayIterator $iterator;

    /**
     * @param array<string,NodeReferenceSnapshot> $references
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
     * @return array<string,NodeReferenceSnapshot>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @param array<string,array<string,mixed>|NodeReferenceSnapshot> $nodeReferences
     */
    public static function fromArray(array $nodeReferences): self
    {
        $values = [];
        foreach ($nodeReferences as $nodeReferenceName => $nodeReferenceValue) {
            if (is_array($nodeReferenceValue)) {
                $values[$nodeReferenceName] = NodeReferenceSnapshot::fromArray($nodeReferenceValue);
            } elseif ($nodeReferenceValue instanceof NodeReferenceSnapshot) {
                $values[$nodeReferenceName] = $nodeReferenceValue;
            } else {
                /** @var mixed $nodeReferenceValue */
                throw new \InvalidArgumentException(sprintf(
                    'Invalid nodeReferences value. Expected instance of %s, got: %s',
                    NodeReferenceSnapshot::class,
                    is_object($nodeReferenceValue) ? get_class($nodeReferenceValue) : gettype($nodeReferenceValue)
                ), 1546524480);
            }
        }

        return new self($values);
    }

    /**
     * @todo what is this supposed to do?
     */
    public static function fromReferences(References $nodeReferences): self
    {
        $values = [];

        return new self($values);
    }

    /**
     * @return \ArrayIterator<string,NodeReferenceSnapshot>
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
     * @return array<string,NodeReferenceSnapshot>
     */
    public function jsonSerialize(): array
    {
        return $this->references;
    }
}
