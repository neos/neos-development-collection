<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * A set of coverage of an origin dimension space point within a node aggregate
 *
 * Each node originating in an origin dimension space point may cover multiple dimension space points
 * via fallback mechanisms. This behavior is encapsulated in this class
 *
 * @implements \IteratorAggregate<string,DimensionSpacePointSet>
 * @internal no part of public APIs
 */
final class CoverageByOrigin implements \IteratorAggregate, \JsonSerializable
{
    /**
     * The actual coverage.
     * Key is the origin hash, value the coverage of this origin
     *
     * @var array<string,DimensionSpacePointSet>
     */
    private array $coverage;

    /**
     * @param array<string,DimensionSpacePointSet> $coverage
     */
    private function __construct(array $coverage)
    {
        $this->coverage = $coverage;
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
     * @return \Traversable<string,DimensionSpacePointSet>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->coverage;
    }

    /**
     * @return array<string,DimensionSpacePointSet>
     */
    public function jsonSerialize(): array
    {
        return $this->coverage;
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
