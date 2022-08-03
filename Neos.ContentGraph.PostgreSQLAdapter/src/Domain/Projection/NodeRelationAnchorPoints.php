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

use Neos\Flow\Annotations as Flow;

/**
 * The node relation anchor points value object collection
 *
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<int,NodeRelationAnchorPoint>
 */
final class NodeRelationAnchorPoints implements \IteratorAggregate, \Countable
{
    /**
     * @var \ArrayIterator<int,NodeRelationAnchorPoint>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<int,NodeRelationAnchorPoint> $nodeRelationAnchorPoints
     */
    private function __construct(
        private array $nodeRelationAnchorPoints
    ) {
        $this->iterator = new \ArrayIterator($nodeRelationAnchorPoints);
    }

    /**
     * @param array<int|string,string|NodeRelationAnchorPoint> $array
     */
    public static function fromArray(array $array): self
    {
        $values = [];
        foreach ($array as $item) {
            if (is_string($item)) {
                $values[] = NodeRelationAnchorPoint::fromString($item);
            } elseif ($item instanceof NodeRelationAnchorPoint) {
                $values[] = $item;
            } else {
                throw new \InvalidArgumentException(
                    'NodeRelationAnchorPoints can only consist of '
                        . NodeRelationAnchorPoint::class . ' objects.',
                    1616603754
                );
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
                array_splice($childNodeAnchors, $pivot, 0, $nodeRelationAnchorPoint);
            } else {
                $childNodeAnchors[] = $nodeRelationAnchorPoint;
            }
        } else {
            $childNodeAnchors[] = $nodeRelationAnchorPoint;
        }

        return self::fromArray($childNodeAnchors);
    }

    public function replace(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        NodeRelationAnchorPoint $replacement
    ): self {
        $childNodeAnchors = $this->nodeRelationAnchorPoints;
        $position = (int)array_search($nodeRelationAnchorPoint, $childNodeAnchors);
        array_splice($childNodeAnchors, $position, 1, $replacement);

        return self::fromArray($childNodeAnchors);
    }

    public function remove(NodeRelationAnchorPoint $nodeRelationAnchorPoint): self
    {
        $childNodeAnchors = $this->nodeRelationAnchorPoints;
        $pivot = array_search($nodeRelationAnchorPoint, $childNodeAnchors);
        if ($pivot !== false) {
            unset($childNodeAnchors[$pivot]);
        }

        return new self($childNodeAnchors);
    }

    /**
     * @return \ArrayIterator<int,NodeRelationAnchorPoint>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function count(): int
    {
        return count($this->nodeRelationAnchorPoints);
    }
}
