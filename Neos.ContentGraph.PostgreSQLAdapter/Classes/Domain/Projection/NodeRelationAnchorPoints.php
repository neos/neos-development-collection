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
use Neos\Flow\Annotations as Flow;

/**
 * The node relation anchor points value object collection
 *
 * @Flow\Proxy(false)
 */
final class NodeRelationAnchorPoints extends ImmutableArrayObject
{
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
        return '{' . implode(',', $this->getArrayCopy()) .  '}';
    }

    public function add(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSibling
    ): self {
        $childNodeAnchors = $this->getArrayCopy();
        if ($succeedingSibling) {
            $pivot = array_search($succeedingSibling, $childNodeAnchors);
            if ($pivot) {
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
        $childNodeAnchors = $this->getArrayCopy();
        $position = array_search($nodeRelationAnchorPoint, $childNodeAnchors);
        array_splice($childNodeAnchors, $position, 1, $replacement);

        return self::fromArray($childNodeAnchors);
    }

    public function remove(NodeRelationAnchorPoint $nodeRelationAnchorPoint): self
    {
        $childNodeAnchors = $this->getArrayCopy();
        $pivot = array_search($nodeRelationAnchorPoint, $childNodeAnchors);
        if ($pivot !== false) {
            unset($childNodeAnchors[$pivot]);
        }

        return new self($childNodeAnchors);
    }

    /**
     * @param mixed $key
     * @return NodeRelationAnchorPoint|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @return array|NodeRelationAnchorPoint[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|NodeRelationAnchorPoint[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }
}
