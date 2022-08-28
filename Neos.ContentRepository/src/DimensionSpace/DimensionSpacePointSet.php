<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\DimensionSpace;

/**
 * A set of points in the dimension space.
 *
 * E.g.: {[language => es, country => ar], [language => es, country => es]}
 * @implements \IteratorAggregate<string,DimensionSpacePoint>
 * @implements \ArrayAccess<string,DimensionSpacePoint>
 * @api
 */
final class DimensionSpacePointSet implements
    \JsonSerializable,
    \Stringable,
    \IteratorAggregate,
    \ArrayAccess,
    \Countable
{
    /**
     * @var array<string,DimensionSpacePoint>
     */
    public readonly array $points;

    /**
     * @var \ArrayIterator<string,DimensionSpacePoint>
     */
    public readonly \ArrayIterator $iterator;

    /**
     * @param array<string|int,DimensionSpacePoint|array<string,string>> $pointCandidates
     *        An array of DimensionSpacePoints or coordinates
     */
    public function __construct(array $pointCandidates)
    {
        $points = [];
        foreach ($pointCandidates as $index => $pointCandidate) {
            if (is_array($pointCandidate)) {
                $pointCandidate = DimensionSpacePoint::fromArray($pointCandidate);
            }

            if (!$pointCandidate instanceof DimensionSpacePoint) {
                throw new \InvalidArgumentException(sprintf('Point %s was not of type DimensionSpacePoint', $index));
            }
            $points[$pointCandidate->hash] = $pointCandidate;
        }
        $this->points = $points;
        $this->iterator = new \ArrayIterator($this->points);
    }

    /**
     * @param array<string,DimensionSpacePoint|array<string,string>> $array
     */
    public static function fromArray(array $array): self
    {
        return new self($array);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return new self(\json_decode($jsonString, true));
    }

    /**
     * @return array<int,string>
     */
    public function getPointHashes(): array
    {
        return array_keys($this->points);
    }

    public function contains(DimensionSpacePoint $point): bool
    {
        return isset($this->points[$point->hash]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->points[$offset]);
    }

    public function offsetGet(mixed $offset): ?DimensionSpacePoint
    {
        return $this->points[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // not going to happen
    }

    public function offsetUnset(mixed $offset): void
    {
        // not going to happen
    }

    public function getUnion(DimensionSpacePointSet $other): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_merge($this->points, $other->points));
    }

    public function getIntersection(DimensionSpacePointSet $other): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_intersect_key($this->points, $other->points));
    }

    public function getDifference(DimensionSpacePointSet $other): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_diff_key($this->points, $other->points));
    }

    /**
     * @return array<int,DimensionSpacePoint>
     */
    public function jsonSerialize(): array
    {
        return array_values($this->points);
    }

    public function count(): int
    {
        return count($this->points);
    }

    public function isEmpty(): bool
    {
        return count($this->points) === 0;
    }

    public function equals(DimensionSpacePointSet $other): bool
    {
        $thisPointHashes = $this->getPointHashes();
        $otherPointHashes = $other->getPointHashes();

        sort($thisPointHashes);
        sort($otherPointHashes);

        return $thisPointHashes === $otherPointHashes;
    }

    /**
     * @return \ArrayIterator<string,DimensionSpacePoint>|DimensionSpacePoint[]
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    /**
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
