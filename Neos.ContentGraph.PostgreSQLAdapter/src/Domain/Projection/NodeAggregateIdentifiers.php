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
    as NodeAggregateIdentifierCollection;

/**
 * The node aggregate identifier value object collection
 *
 * @internal
 */
final class NodeAggregateIdentifiers
{
    /**
     * @var array<string,NodeAggregateId>
     */
    private array $identifiers;

    /**
     * @param array<string,NodeAggregateId> $identifiers
     */
    private function __construct(array $identifiers)
    {
        $this->identifiers = $identifiers;
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
                    'NodeAggregateIdentifiers can only consist of '
                        . NodeAggregateId::class . ' objects.',
                    1616841637
                );
            }
        }

        return new self($values);
    }

    public static function fromCollection(
        NodeAggregateIdentifierCollection $collection
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
        return '{' . implode(',', $this->identifiers) .  '}';
    }

    public function add(
        NodeAggregateId $nodeAggregateIdentifier,
        ?NodeAggregateId $succeedingSibling = null
    ): self {
        $nodeAggregateIdentifiers = $this->identifiers;
        if ($succeedingSibling) {
            $pivot = (int)array_search($succeedingSibling, $nodeAggregateIdentifiers);
            array_splice($nodeAggregateIdentifiers, $pivot, 0, $nodeAggregateIdentifier);
        } else {
            $nodeAggregateIdentifiers[(string)$nodeAggregateIdentifier] = $nodeAggregateIdentifier;
        }

        return new self($nodeAggregateIdentifiers);
    }

    public function remove(NodeAggregateId $nodeAggregateIdentifier): self
    {
        $identifiers = $this->identifiers;
        if (isset($identifiers[(string) $nodeAggregateIdentifier])) {
            unset($identifiers[(string) $nodeAggregateIdentifier]);
        }

        return new self($identifiers);
    }

    public function isEmpty(): bool
    {
        return count($this->identifiers) === 0;
    }
}
