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

/**
 * The node relation anchor points value object collection
 *
 * @implements \IteratorAggregate<NodeRelationAnchorPoint>
 * @internal
 */
final readonly class NodeRelationAnchorPoints implements \IteratorAggregate, \Countable
{
    /**
     * @var array<NodeRelationAnchorPoint>
     */
    public array $nodeRelationAnchorPoints;

    public function __construct(NodeRelationAnchorPoint ...$nodeRelationAnchorPoints)
    {
        $this->nodeRelationAnchorPoints = $nodeRelationAnchorPoints;
    }

    /**
     * @param array<string|NodeRelationAnchorPoint> $array
     */
    public static function fromArray(array $array): self
    {
        $values = [];
        foreach ($array as $item) {
            if (is_string($item)) {
                $values[] = NodeRelationAnchorPoint::fromString($item);
            } elseif ($item instanceof NodeRelationAnchorPoint) {
                $values[] = $item;
            }
        }
        return new self(...$values);
    }

    public static function fromDatabaseString(string $databaseString): self
    {
        return self::fromArray(\explode(',', \trim($databaseString, '{}')));
    }

    public function toDatabaseString(): string
    {
        return '{' . implode(',', $this->nodeRelationAnchorPoints) .  '}';
    }

    public function add(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSibling
    ): self {
        $childNodeAnchors = $this->nodeRelationAnchorPoints;
        if ($succeedingSibling) {
            $pivot = array_search($succeedingSibling, $childNodeAnchors);
            if (is_int($pivot)) {
                array_splice($childNodeAnchors, $pivot, 0, [$nodeRelationAnchorPoint]);
            } else {
                $childNodeAnchors[] = $nodeRelationAnchorPoint;
            }
        } else {
            $childNodeAnchors[] = $nodeRelationAnchorPoint;
        }

        return new self(...$childNodeAnchors);
    }

    public function replace(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        NodeRelationAnchorPoint $replacement
    ): self {
        $childNodeAnchors = $this->nodeRelationAnchorPoints;
        $position = (int)array_search($nodeRelationAnchorPoint, $childNodeAnchors);
        array_splice($childNodeAnchors, $position, 1, [$replacement]);

        return new self(...$childNodeAnchors);
    }

    public function remove(NodeRelationAnchorPoint $nodeRelationAnchorPoint): self
    {
        $childNodeAnchors = $this->nodeRelationAnchorPoints;
        $pivot = array_search($nodeRelationAnchorPoint, $childNodeAnchors);
        if ($pivot !== false) {
            unset($childNodeAnchors[$pivot]);
        }

        return new self(...$childNodeAnchors);
    }

    /**
     * @return \Traversable<NodeRelationAnchorPoint>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->nodeRelationAnchorPoints;
    }

    public function count(): int
    {
        return count($this->nodeRelationAnchorPoints);
    }
}
