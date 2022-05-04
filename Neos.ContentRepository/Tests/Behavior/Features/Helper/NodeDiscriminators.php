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

namespace Neos\ContentRepository\Tests\Behavior\Features\Helper;

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\Nodes;
use Neos\Flow\Annotations as Flow;

/**
 * The node discriminator value object collection
 *
 * @implements \IteratorAggregate<string,NodeDiscriminator>
 * @implements \ArrayAccess<string,NodeDiscriminator>
 */
#[Flow\Proxy(false)]
final class NodeDiscriminators implements \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    /**
     * @var array<int,NodeDiscriminator>
     */
    private array $discriminators;

    /**
     * @var \ArrayIterator<int,NodeDiscriminator>
     */
    private \ArrayIterator $iterator;

    private function __construct(NodeDiscriminator ...$iterable)
    {
        $this->discriminators = $iterable;
        $this->iterator = new \ArrayIterator($iterable);
    }

    public static function fromJsonString(string $jsonString): self
    {
        $discriminators = \json_decode($jsonString, true);

        return self::fromArray($discriminators);
    }

    public static function fromArray(array $array): self
    {
        return new self(...array_map(
            fn (string $shorthand): NodeDiscriminator => NodeDiscriminator::fromShorthand($shorthand),
            $array
        ));
    }

    public static function fromNodes(Nodes $nodes): self
    {
        return new self(...array_map(
            fn (NodeInterface $node): NodeDiscriminator => NodeDiscriminator::fromNode($node),
            $nodes->getIterator()->getArrayCopy()
        ));
    }

    public function equal(NodeDiscriminators $other): bool
    {
        return $this->discriminators == $other->getIterator()->getArrayCopy();
    }

    public function areSimilarTo(NodeDiscriminators $other): bool
    {
        $theseDiscriminators = $this->discriminators;
        sort($theseDiscriminators);
        $otherDiscriminators = $other->getIterator()->getArrayCopy();
        sort($otherDiscriminators);

        return $theseDiscriminators == $otherDiscriminators;
    }

    /**
     * @return \ArrayIterator<int,NodeDiscriminator>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetGet(mixed $offset): ?NodeDiscriminator
    {
        return $this->discriminators[$offset] ?? null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->discriminators[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodeDiscriminators.', 1643561864);
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodeDiscriminators.', 1643561864);
    }

    /**
     * @return array<int,NodeDiscriminator>
     */
    public function jsonSerialize(): array
    {
        return $this->discriminators;
    }
}
