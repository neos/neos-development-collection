<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\Flow\Annotations as Flow;

/**
 * The node discriminator value object collection
 *
 * @implements \IteratorAggregate<string,ContentGraphInterface>
 * @implements \ArrayAccess<string,ContentGraphInterface>
 */
#[Flow\Proxy(false)]
final class NodeDiscriminators implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<int,NodeDiscriminator>
     */
    private array $discriminators;

    /**
     * @var \ArrayIterator<int,NodeDiscriminator>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<int,NodeDiscriminator> $iterable
     */
    private function __construct(iterable $iterable)
    {
        $discriminators = [];
        foreach ($iterable as $item) {
            if (!$item instanceof NodeDiscriminator) {
                throw new \InvalidArgumentException('ContentGraphs can only consist of ' . NodeDiscriminator::class . ' objects.', 1643561582);
            }
            $discriminators[] = $item;
        }

        $this->discriminators = $discriminators;
        $this->iterator = new \ArrayIterator($discriminators);
    }

    public static function fromJsonString(string $jsonString): self
    {
        $discriminators = \json_decode($jsonString, true);

        return self::fromArray($discriminators);
    }

    public static function fromArray(array $array): self
    {
        return new self(array_map(
            function (string $shorthand) {
                return NodeDiscriminator::fromShorthand($shorthand);
            },
            $array
        ));
    }

    public static function fromNodes(Nodes $nodes): self
    {
        return new self(array_map(
            function (NodeInterface $node) {
                return NodeDiscriminator::fromNode($node);
            },
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

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodeDiscriminators.', 1643561864);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodeDiscriminators.', 1643561864);
    }
}
