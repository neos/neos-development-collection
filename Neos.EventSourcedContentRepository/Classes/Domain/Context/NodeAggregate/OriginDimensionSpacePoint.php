<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;

/**
 * A node's origin dimension space point. Defines in which point in the dimension space the node originates.
 *
 * Example:
 * In a setup with dimension "language", a node that originates in English has English content,
 * but might be visible in other languages via fallback mechanisms.
 *
 * @Flow\Proxy(false)
 */
final class OriginDimensionSpacePoint extends DimensionSpacePoint
{
    /**
     * @var array<string,OriginDimensionSpacePoint>
     */
    private static array $instances = [];

    /**
     * @param array<string,string> $coordinates
     */
    public static function instance(array $coordinates): self
    {
        $hash = self::hashCoordinates($coordinates);
        if (!isset(self::$instances[$hash])) {
            self::validateCoordinates($coordinates);
            self::$instances[$hash] = new self($coordinates, $hash);
        }

        return self::$instances[$hash];
    }

    public static function fromArray(array $data): self
    {
        return self::instance($data);
    }

    /**
     * @param string $jsonString A JSON string representation, see jsonSerialize
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::instance(json_decode($jsonString, true));
    }

    public static function fromDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return self::instance($dimensionSpacePoint->coordinates);
    }

    public function toDimensionSpacePoint(): DimensionSpacePoint
    {
        return DimensionSpacePoint::instance(($this->coordinates));
    }
}
