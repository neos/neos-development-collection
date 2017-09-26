<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Utility\Arrays;

/**
 * A point in the dimension space with coordinates DimensionName => DimensionValue.
 *
 * E.g.: ["language" => "es", "country" => "ar"]
 */
final class DimensionSpacePoint implements \JsonSerializable
{
    /**
     * @var array
     */
    private $coordinates;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @param array $coordinates
     */
    public function __construct(array $coordinates)
    {
        foreach ($coordinates as $dimensionName => $dimensionValue) {
            if (!is_string($dimensionValue)) {
                throw new \InvalidArgumentException(sprintf('Dimension value for %s is not a string', $dimensionName), 1506076562);
            }
            if ($dimensionValue === '') {
                throw new \InvalidArgumentException('Dimension value must not be empty', 1506076563);
            }
        }

        $this->coordinates = $coordinates;
        $identityComponents = $coordinates;
        Arrays::sortKeysRecursively($identityComponents);

        $this->hash = md5(json_encode($identityComponents));
    }

    /**
     * @param array $legacyDimensionValues Array from dimension name to dimension values
     * @return static
     */
    public static function fromLegacyDimensionArray(array $legacyDimensionValues): DimensionSpacePoint
    {
        $coordinates = [];
        foreach ($legacyDimensionValues as $dimensionName => $rawDimensionValues) {
            $coordinates[$dimensionName] = reset($rawDimensionValues);
        }

        return new DimensionSpacePoint($coordinates);
    }

    /**
     * @return array
     */
    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return array
     */
    public function toLegacyDimensionArray(): array
    {
        $legacyDimensions = [];
        foreach ($this->coordinates as $dimensionName => $dimensionValue) {
            $legacyDimensions[$dimensionName] = [$dimensionValue];
        }

        return $legacyDimensions;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['coordinates' => $this->coordinates];
    }

    public function __toString(): string
    {
        return 'dimension space point:' . json_encode($this->coordinates);
    }
}
