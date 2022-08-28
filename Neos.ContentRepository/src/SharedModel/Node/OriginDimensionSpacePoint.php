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

namespace Neos\ContentRepository\SharedModel\Node;

use Neos\ContentRepository\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;

/**
 * A node's origin dimension space point. Defines in which point in the dimension space the node originates
 * (= is "at home"). Every node has exactly ONE OriginDimensionSpacePoint, but one or more {@see DimensionSpacePoint}s
 * where the node is visible.
 *
 * Example:
 * In a setup with dimension "language", a node that originates in English has English content,
 * but might be visible in other languages via fallback mechanisms.
 *
 * @api
 */
final class OriginDimensionSpacePoint extends AbstractDimensionSpacePoint
{
    /**
     * @var array<string,OriginDimensionSpacePoint>
     */
    private static array $instances = [];

    /**
     * @param array<string,string> $coordinates
     */
    private static function instance(array $coordinates): self
    {
        $hash = self::hashCoordinates($coordinates);
        if (!isset(self::$instances[$hash])) {
            self::validateCoordinates($coordinates);
            self::$instances[$hash] = new self($coordinates, $hash);
        }

        return self::$instances[$hash];
    }

    /**
     * @param array<string,string> $data
     */
    public static function fromArray(array $data): self
    {
        return self::instance($data);
    }

    /**
     * @param string $jsonString A JSON string representation, see jsonSerialize
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::instance(json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public static function fromDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return self::instance($dimensionSpacePoint->coordinates);
    }

    /**
     * Creates a dimension space point from a legacy dimension array in format
     * ['language' => ['es'], 'country' => ['ar']]
     *
     * @param array<string,array<int,string>> $legacyDimensionValues
     * @deprecated should be only used for conversion from Neos <= 8.x to 9.x upwards. never use this in "modern" code.
     */
    final public static function fromLegacyDimensionArray(array $legacyDimensionValues): self
    {
        $coordinates = [];
        foreach ($legacyDimensionValues as $dimensionName => $rawDimensionValues) {
            /** @var string $primaryDimensionValue */
            $primaryDimensionValue = reset($rawDimensionValues);
            $coordinates[$dimensionName] = $primaryDimensionValue;
        }

        return self::instance($coordinates);
    }

    public function toDimensionSpacePoint(): DimensionSpacePoint
    {
        return DimensionSpacePoint::fromArray(($this->coordinates));
    }
}
