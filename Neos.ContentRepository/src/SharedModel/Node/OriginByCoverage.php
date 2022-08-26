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
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;

/**
 * A set of origin of a covered dimension space point within a node aggregate
 *
 * Each dimension space point node covered by a node aggregate originates in a single node.
 * This behavior is encapsulated in this class
 *
 * @implements \IteratorAggregate<string,OriginDimensionSpacePoint>
 */
final class OriginByCoverage implements \IteratorAggregate, \JsonSerializable, \Stringable
{
    /**
     * The set of origins.
     * Key is the covered hash, value the origin that covers it
     *
     * @var array<string,OriginDimensionSpacePoint>
     */
    private array $origins;

    /**
     * @var \ArrayIterator<string,OriginDimensionSpacePoint>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<string,OriginDimensionSpacePoint> $coverage
     */
    private function __construct(array $coverage)
    {
        $this->origins = $coverage;
        $this->iterator = new \ArrayIterator($coverage);
    }

    /**
     * @param array<string,array<string,string>|OriginDimensionSpacePoint> $array
     */
    public static function fromArray(array $array): self
    {
        $origins = [];
        foreach ($array as $coveredHash => $origin) {
            if (is_array($origin)) {
                $origins[$coveredHash] = OriginDimensionSpacePoint::fromArray($origin);
            } elseif ($origin instanceof OriginDimensionSpacePoint) {
                $origins[$coveredHash] = $origin;
            } else {
                throw new \InvalidArgumentException(
                    'OriginByCoverage may only consist of ' . OriginDimensionSpacePoint::class . ' objects.',
                    1645808183
                );
            }
        }

        return new self($origins);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(json_decode($jsonString, true));
    }

    public function getOrigin(DimensionSpacePoint $coveredDimensionSpacePoint): ?OriginDimensionSpacePoint
    {
        return $this->origins[$coveredDimensionSpacePoint->hash] ?? null;
    }

    /**
     * @return \ArrayIterator<string,OriginDimensionSpacePoint>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    /**
     * @return array<string,OriginDimensionSpacePoint>
     */
    public function jsonSerialize(): array
    {
        return $this->origins;
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
