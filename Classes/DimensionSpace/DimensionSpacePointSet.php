<?php

declare(strict_types=1);

namespace Neos\ContentRepository\DimensionSpace\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A set of points in the dimension space.
 *
 * E.g.: {[language => es, country => ar], [language => es, country => es]}
 *
 * @Flow\Proxy(false)
 */
final class DimensionSpacePointSet implements \JsonSerializable, \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array|DimensionSpacePoint[]
     */
    private $points;

    /**
     * @var \ArrayIterator
     */
    private $iterator;

    /**
     * @param array|DimensionSpacePoint[] $points Array of dimension space points
     */
    public function __construct(array $points)
    {
        $this->points = [];
        foreach ($points as $index => $point) {
            if (is_array($point)) {
                $point = new DimensionSpacePoint($point);
            }

            if (!$point instanceof DimensionSpacePoint) {
                throw new \InvalidArgumentException(sprintf('Point %s was not of type DimensionSpacePoint', $index));
            }
            $this->points[$point->getHash()] = $point;
        }
        $this->iterator = new \ArrayIterator($this->points);
    }

    public static function fromArray(array $array): self
    {
        return new self($array);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return new self(\json_decode($jsonString, true));
    }

    /**
     * @return array|DimensionSpacePoint[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    public function getPointHashes(): array
    {
        return array_keys($this->points);
    }

    public function contains(DimensionSpacePoint $point): bool
    {
        return isset($this->points[$point->getHash()]);
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
     * @return \ArrayIterator|DimensionSpacePoint[]
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists($dimensionSpacePointHash): bool
    {
        return isset($this->points[$dimensionSpacePointHash]);
    }

    public function offsetGet($dimensionSpacePointHash): ?DimensionSpacePoint
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

    public function getUnion(DimensionSpacePointSet $other): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_merge($this->points, $other->getPoints()));
    }

    public function getIntersection(DimensionSpacePointSet $other): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_intersect_key($this->points, $other->getPoints()));
    }

    public function getDifference(DimensionSpacePointSet $other): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_diff_key($this->points, $other->getPoints()));
    }
}
