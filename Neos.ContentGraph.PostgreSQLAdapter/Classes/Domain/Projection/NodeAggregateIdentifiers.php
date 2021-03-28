<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\PostgreSQLAdapter\Domain\ImmutableArrayObject;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The node aggregate identifier value object collection
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateIdentifiers extends ImmutableArrayObject
{
    public static function fromArray(array $array): self
    {
        $values = [];
        foreach ($array as $item) {
            if (is_string($item)) {
                $values[$item] = NodeAggregateIdentifier::fromString($item);
            } elseif($item instanceof NodeAggregateIdentifier) {
                $values[(string)$item] = $item;
            } else {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . NodeAggregateIdentifier::class . ' objects.', 1616841637);
            }
        }

        return new self($values);
    }
    public static function fromDatabaseString(string $databaseString): self
    {
        return self::fromArray(\explode(',', \trim($databaseString, '{}')));
    }

    public function toDatabaseString(): string
    {
        return '{' . implode(',', $this->getArrayCopy()) .  '}';
    }

    public function add(NodeAggregateIdentifier $nodeAggregateIdentifier, ?NodeAggregateIdentifier $succeedingSibling = null): self
    {
        $nodeAggregateIdentifiers = $this->getArrayCopy();
        if ($succeedingSibling) {
            $pivot = array_search($succeedingSibling, $nodeAggregateIdentifiers);
            array_splice($nodeAggregateIdentifiers, $pivot, 0, $nodeAggregateIdentifier);
        } else {
            $nodeAggregateIdentifiers[] = $nodeAggregateIdentifier;
        }

        return self::fromArray($nodeAggregateIdentifiers);
    }

    /**
     * @param mixed $key
     * @return NodeAggregateIdentifier|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @return array|NodeAggregateIdentifier[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|NodeAggregateIdentifier[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }
}
