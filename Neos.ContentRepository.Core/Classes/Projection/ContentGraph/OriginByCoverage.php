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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * A set of origin of a covered dimension space point within a node aggregate
 *
 * Each dimension space point node covered by a node aggregate originates in a single node.
 * This behavior is encapsulated in this class
 *
 * @implements \IteratorAggregate<string,OriginDimensionSpacePoint>
 * @internal no part of public APIs
 */
final class OriginByCoverage implements \IteratorAggregate, \JsonSerializable
{
    /**
     * The set of origins.
     * Key is the covered hash, value the origin that covers it
     *
     * @var array<string,OriginDimensionSpacePoint>
     */
    private array $origins;

    /**
     * @param array<string,OriginDimensionSpacePoint> $coverage
     */
    private function __construct(array $coverage)
    {
        $this->origins = $coverage;
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
        try {
            return self::fromArray(json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to JSON-decode "%s" for %s instance: %s', $jsonString, self::class, $e->getMessage()), 1716574748, $e);
        }
    }

    public function getOrigin(DimensionSpacePoint $coveredDimensionSpacePoint): ?OriginDimensionSpacePoint
    {
        return $this->origins[$coveredDimensionSpacePoint->hash] ?? null;
    }

    /**
     * @return \Traversable<string,OriginDimensionSpacePoint>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->origins;
    }

    /**
     * @return array<string,OriginDimensionSpacePoint>
     */
    public function jsonSerialize(): array
    {
        return $this->origins;
    }

    public function toJson(): string
    {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-encode instance of %s: %s', self::class, $e->getMessage()), 1716575158, $e);
        }
    }
}
