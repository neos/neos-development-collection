<?php

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.EventSourcedContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\Flow\Annotations as Flow;

/**
 * A set of points in the dimension space, occupied by nodes in a node aggregate
 *
 * E.g.: {[language => es, country => ar], [language => es, country => es]}
 *
 * @Flow\Proxy(false)
 */
final class OriginDimensionSpacePointSet implements \JsonSerializable, \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array|OriginDimensionSpacePoint[]
     */
    private array $points;

    /**
     * @var \ArrayIterator
     */
    private \ArrayIterator $iterator;

    /**
     * @param array|OriginDimensionSpacePoint[] $points Array of dimension space points
     */
    public function __construct(array $points)
    {
        $this->points = [];
        foreach ($points as $index => $point) {
            if (is_array($point)) {
                $point = OriginDimensionSpacePoint::instance($point);
            }

            if (!$point instanceof OriginDimensionSpacePoint) {
                throw new \InvalidArgumentException(sprintf('Point %s was not of type OriginDimensionSpacePoint', $index));
            }
            $this->points[$point->hash] = $point;
        }
        $this->iterator = new \ArrayIterator($this->points);
    }

    public static function fromDimensionSpacePointSet(DimensionSpacePointSet $dimensionSpacePointSet): self
    {
        $originDimensionSpacePoints = [];
        foreach ($dimensionSpacePointSet->points as $point) {
            $originDimensionSpacePoints[] = OriginDimensionSpacePoint::fromDimensionSpacePoint($point);
        }

        return new self($originDimensionSpacePoints);
    }

    public static function fromJsonString(string $jsonString): self
    {
        $dimensionSpacePoints = [];
        foreach (json_decode($jsonString, true) as $coordinates) {
            $dimensionSpacePoints[] = OriginDimensionSpacePoint::instance($coordinates);
        }

        return new self($dimensionSpacePoints);
    }

    public function toDimensionSpacePointSet(): DimensionSpacePointSet
    {
        $dimensionSpacePoints = [];
        foreach ($this->points as $point) {
            $dimensionSpacePoints[] = $point->toDimensionSpacePoint();
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @return array|OriginDimensionSpacePoint[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    public function getPointHashes(): array
    {
        return array_keys($this->points);
    }

    public function contains(OriginDimensionSpacePoint $point): bool
    {
        return isset($this->points[$point->hash]);
    }

    public function __toString(): string
    {
        return json_encode($this);
    }

    public function jsonSerialize(): array
    {
        return array_values($this->points);
    }

    public function count(): int
    {
        return count($this->points);
    }

    /**
     * @return \ArrayIterator|OriginDimensionSpacePoint[]
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists($dimensionSpacePointHash): bool
    {
        return isset($this->points[$dimensionSpacePointHash]);
    }

    public function offsetGet($dimensionSpacePointHash): ?OriginDimensionSpacePoint
    {
        return $this->points[$dimensionSpacePointHash] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        // not going to happen
    }

    public function offsetUnset($offset)
    {
        // not going to happen
    }

    public function getUnion(OriginDimensionSpacePointSet $other): OriginDimensionSpacePointSet
    {
        return new OriginDimensionSpacePointSet(array_merge($this->points, $other->getPoints()));
    }

    public function getIntersection(OriginDimensionSpacePointSet $other): OriginDimensionSpacePointSet
    {
        return new OriginDimensionSpacePointSet(array_intersect_key($this->points, $other->getPoints()));
    }

    public function getDifference(OriginDimensionSpacePointSet $other): OriginDimensionSpacePointSet
    {
        return new OriginDimensionSpacePointSet(array_diff_key($this->points, $other->getPoints()));
    }
}
