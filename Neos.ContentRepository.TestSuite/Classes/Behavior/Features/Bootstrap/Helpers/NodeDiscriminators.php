<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;

/**
 * The node discriminator value object collection
 *
 * @implements \IteratorAggregate<int|string,NodeDiscriminator>
 * @implements \ArrayAccess<int|string,NodeDiscriminator>
 */
final class NodeDiscriminators implements \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    /**
     * @var array<int|string,NodeDiscriminator>
     */
    private array $discriminators;

    private function __construct(NodeDiscriminator ...$iterable)
    {
        $this->discriminators = $iterable;
    }

    public static function fromJsonString(string $jsonString): self
    {
        $discriminators = \json_decode($jsonString, true);

        return self::fromArray($discriminators);
    }

    /**
     * @param array<string> $array
     */
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
            fn (Node $node): NodeDiscriminator => NodeDiscriminator::fromNode($node),
            iterator_to_array($nodes)
        ));
    }

    public function equal(self $other): bool
    {
        return $this->discriminators == $other->discriminators;
    }

    public function areSimilarTo(self $other): bool
    {
        $theseDiscriminators = $this->discriminators;
        \sort($theseDiscriminators);
        $otherDiscriminators = iterator_to_array($other);
        \sort($otherDiscriminators);

        return $theseDiscriminators == $otherDiscriminators;
    }

    /**
     * @return \Traversable<int|string,NodeDiscriminator>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->discriminators;
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
     * @return array<int|string,NodeDiscriminator>
     */
    public function jsonSerialize(): array
    {
        return $this->discriminators;
    }
}
