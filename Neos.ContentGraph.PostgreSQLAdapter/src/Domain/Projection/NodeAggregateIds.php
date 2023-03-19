<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds
    as NodeAggregateIdCollection;

/**
 * The node aggregate identifier value object collection
 *
 * @internal
 */
final class NodeAggregateIds
{
    /**
     * @var array<string,NodeAggregateId>
     */
    private array $ids;

    /**
     * @param array<string,NodeAggregateId> $identifiers
     */
    private function __construct(array $identifiers)
    {
        $this->ids = $identifiers;
    }

    /**
     * @param array<int|string,string|NodeAggregateId> $array
     */
    public static function fromArray(array $array): self
    {
        $values = [];
        foreach ($array as $item) {
            if (is_string($item)) {
                $values[$item] = NodeAggregateId::fromString($item);
            } elseif ($item instanceof NodeAggregateId) {
                $values[(string)$item] = $item;
            } else {
                throw new \InvalidArgumentException(
                    'NodeAggregateIds can only consist of '
                        . NodeAggregateId::class . ' objects.',
                    1616841637
                );
            }
        }

        return new self($values);
    }

    public static function fromCollection(
        NodeAggregateIdCollection $collection
    ): self {
        return new self(
            $collection->getIterator()->getArrayCopy()
        );
    }

    public static function fromDatabaseString(string $databaseString): self
    {
        return self::fromArray(\explode(',', \trim($databaseString, '{}')));
    }

    public function toDatabaseString(): string
    {
        return '{' . implode(',', $this->ids) .  '}';
    }

    public function add(
        NodeAggregateId $nodeAggregateId,
        ?NodeAggregateId $succeedingSibling = null
    ): self {
        $nodeAggregateIds = $this->ids;
        if ($succeedingSibling) {
            $pivot = (int)array_search($succeedingSibling, $nodeAggregateIds);
            array_splice($nodeAggregateIds, $pivot, 0, $nodeAggregateId);
        } else {
            $nodeAggregateIds[(string)$nodeAggregateId] = $nodeAggregateId;
        }

        return new self($nodeAggregateIds);
    }

    public function remove(NodeAggregateId $nodeAggregateId): self
    {
        $identifiers = $this->ids;
        if (isset($identifiers[(string) $nodeAggregateId])) {
            unset($identifiers[(string) $nodeAggregateId]);
        }

        return new self($identifiers);
    }

    public function isEmpty(): bool
    {
        return count($this->ids) === 0;
    }
}
