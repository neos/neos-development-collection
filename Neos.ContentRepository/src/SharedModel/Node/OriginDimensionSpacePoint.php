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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;

/**
 * A node's origin dimension space point. Defines in which point in the dimension space the node originates.
 *
 * Example:
 * In a setup with dimension "language", a node that originates in English has English content,
 * but might be visible in other languages via fallback mechanisms.
 */
#[Flow\Proxy(false)]
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
        return self::instance(json_decode($jsonString, true));
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
