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

namespace Neos\ContentRepository\DimensionSpace\DimensionSpace;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * A point in the dimension space with coordinates DimensionName => DimensionValue.
 * E.g.: ["language" => "es", "country" => "ar"]
 *
 * Implements CacheAwareInterface because of Fusion Runtime caching and Routing
 */
#[Flow\Proxy(false)]
final class DimensionSpacePoint extends AbstractDimensionSpacePoint
{
    /**
     * @var array<string,DimensionSpacePoint>
     */
    private static array $instances = [];

    /**
     * @param array<string,string> $coordinates
     */
    private static function instance(array $coordinates): self
    {
        $hash = parent::hashCoordinates($coordinates);
        if (!isset(self::$instances[$hash])) {
            parent::validateCoordinates($coordinates);
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
     * Creates a dimension space point from a JSON string representation
     * See jsonSerialize
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::instance(json_decode($jsonString, true));
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
            $coordinates[$dimensionName] = reset($rawDimensionValues);
        }

        return self::instance($coordinates);
    }

    final public static function fromUriRepresentation(string $encoded): self
    {
        return self::instance(json_decode(base64_decode($encoded), true));
    }

    /**
     * Varies a dimension space point in a single coordinate
     */
    final public function vary(Dimension\ContentDimensionIdentifier $dimensionIdentifier, string $value): self
    {
        $variedCoordinates = $this->coordinates;
        $variedCoordinates[(string)$dimensionIdentifier] = $value;

        return self::instance($variedCoordinates);
    }
}
