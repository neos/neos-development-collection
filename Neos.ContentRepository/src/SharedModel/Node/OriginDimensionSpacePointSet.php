<?php

declare(strict_types=1);

namespace Neos\ContentRepository\SharedModel\Node;

/*
 * This file is part of the Neos.ContentRepository package.
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
 * @implements \IteratorAggregate<string,OriginDimensionSpacePoint>
 * @implements \ArrayAccess<string,OriginDimensionSpacePoint>
 *
 * @api
 */
#[Flow\Proxy(false)]
final class OriginDimensionSpacePointSet implements \JsonSerializable, \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array<string,OriginDimensionSpacePoint>
     */
    private array $points;

    /**
     * @var \ArrayIterator<string,OriginDimensionSpacePoint>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<int|string,OriginDimensionSpacePoint|array<string,string>> $points
     */
    public function __construct(array $points)
    {
        $this->points = [];
        foreach ($points as $index => $point) {
            if (is_array($point)) {
                $point = OriginDimensionSpacePoint::fromArray($point);
            }

            if (!$point instanceof OriginDimensionSpacePoint) {
                throw new \InvalidArgumentException(sprintf(
                    'Point %s was not of type OriginDimensionSpacePoint',
                    $index
                ));
            }
            $this->points[$point->hash] = $point;
        }
        $this->iterator = new \ArrayIterator($this->points);
    }

    public static function fromDimensionSpacePointSet(DimensionSpacePointSet $dimensionSpacePointSet): self
    {
        $originDimensionSpacePoints = [];
        foreach ($dimensionSpacePointSet->points as $point) {
            $originDimensionSpacePoints[$point->hash] = OriginDimensionSpacePoint::fromDimensionSpacePoint($point);
        }

        return new self($originDimensionSpacePoints);
    }

    /**
     * @param array<int,array<string,string>> $array
     */
    public static function fromArray(array $array): self
    {
        $dimensionSpacePoints = [];
        foreach ($array as $coordinates) {
            $dimensionSpacePoints[] = OriginDimensionSpacePoint::fromArray($coordinates);
        }
        return new self($dimensionSpacePoints);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(json_decode($jsonString, true));
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

    /**
     * @return array<int,string>
     */
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
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int,OriginDimensionSpacePoint>
     */
    public function jsonSerialize(): array
    {
        return array_values($this->points);
    }

    public function count(): int
    {
        return count($this->points);
    }

    /**
     * @return \ArrayIterator<string,OriginDimensionSpacePoint>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    /**
     * @param string $dimensionSpacePointHash
     */
    public function offsetExists(mixed $dimensionSpacePointHash): bool
    {
        return isset($this->points[$dimensionSpacePointHash]);
    }

    /**
     * @param string $dimensionSpacePointHash
     */
    public function offsetGet(mixed $dimensionSpacePointHash): ?OriginDimensionSpacePoint
    {
        return $this->points[$dimensionSpacePointHash] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException('Cannot modify immutable OriginDimensionSpacePointSet', 1643467297);
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException('Cannot modify immutable OriginDimensionSpacePointSet', 1643467297);
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
