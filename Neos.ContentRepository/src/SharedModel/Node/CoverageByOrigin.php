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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * A set of coverage of an origin dimension space point within a node aggregate
 *
 * Each node originating in an origin dimension space point may cover multiple dimension space points
 * via fallback mechanisms. This behavior is encapsulated in this class
 *
 * @implements \IteratorAggregate<string,DimensionSpacePointSet>
 */
#[Flow\Proxy(false)]
final class CoverageByOrigin implements \IteratorAggregate, \JsonSerializable, \Stringable
{
    /**
     * The actual coverage.
     * Key is the origin hash, value the coverage of this origin
     *
     * @var array<string,DimensionSpacePointSet>
     */
    private array $coverage;

    /**
     * @var \ArrayIterator<string,DimensionSpacePointSet>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<string,DimensionSpacePointSet> $coverage
     */
    private function __construct(array $coverage)
    {
        $this->coverage = $coverage;
        $this->iterator = new \ArrayIterator($coverage);
    }

    /**
     * @param array<string,array<string,array<string,string>|DimensionSpacePoint>> $array
     */
    public static function fromArray(array $array): self
    {
        $coverage = [];
        foreach ($array as $originHash => $rawCoverage) {
            $coverage[$originHash] = DimensionSpacePointSet::fromArray($rawCoverage);
        }

        return new self($coverage);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(json_decode($jsonString, true));
    }

    public function getCoverage(OriginDimensionSpacePoint $originDimensionSpacePoint): ?DimensionSpacePointSet
    {
        return $this->coverage[$originDimensionSpacePoint->hash] ?? null;
    }

    /**
     * @return \ArrayIterator<string,DimensionSpacePointSet>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    /**
     * @return array<string,DimensionSpacePointSet>
     */
    public function jsonSerialize(): array
    {
        return $this->coverage;
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
