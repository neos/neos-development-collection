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

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * A point in the dimension space with coordinates DimensionName => DimensionValue.
 * E.g.: ["language" => "es", "country" => "ar"]
 *
 * Implements CacheAwareInterface because of Fusion Runtime caching and Routing
 */
#[Flow\Proxy(false)]
class DimensionSpacePoint implements \JsonSerializable, CacheAwareInterface, ProtectedContextAwareInterface
{
    private static array $instances = [];

    private function __construct(
        /**
         * @var array<string,string>
         */
        public readonly array $coordinates,
        public readonly string $hash
    ) {}

    /**
     * @param array<string,string> $coordinates
     */
    public static function instance(array $coordinates): self
    {
        $identityComponents = $coordinates;
        ksort($identityComponents);
        $hash = md5(json_encode($identityComponents));
        if (!isset(self::$instances[$hash])) {
            foreach ($coordinates as $dimensionName => $dimensionValue) {
                if (!is_string($dimensionName)) {
                    throw new \InvalidArgumentException(
                        sprintf('Dimension name "%s" is not a string', $dimensionName),
                        1639733101
                    );
                }
                if ($dimensionName === '') {
                    throw new \InvalidArgumentException('Dimension name must not be empty', 1639733123);
                }
                if (!is_string($dimensionValue)) {
                    throw new \InvalidArgumentException(
                        sprintf('Dimension value for %s is not a string', $dimensionName),
                        1506076562
                    );
                }
                if ($dimensionValue === '') {
                    throw new \InvalidArgumentException('Dimension value must not be empty', 1506076563);
                }
            }
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

    /**
     * A variant VarA is a "Direct Variant in Dimension Dim" of another variant VarB, if VarA and VarB are sharing all dimension values except in "Dim",
     * AND they have differing dimension values in "Dim". Thus, VarA and VarB only vary in the given "Dim".
     * It does not say anything about how VarA and VarB relate (if it is specialization, peer or generalization).
     */
    final public function isDirectVariantInDimension(
        DimensionSpacePoint $other,
        Dimension\ContentDimensionIdentifier $contentDimensionIdentifier
    ): bool {
        if (!$this->hasCoordinate($contentDimensionIdentifier) || !$other->hasCoordinate($contentDimensionIdentifier)) {
            return false;
        }
        if ($this->coordinates[(string)$contentDimensionIdentifier] === $other->coordinates[(string)$contentDimensionIdentifier]) {
            return false;
        }

        $theseCoordinates = $this->coordinates;
        $otherCoordinates = $other->coordinates;
        unset($theseCoordinates[(string)$contentDimensionIdentifier]);
        unset($otherCoordinates[(string)$contentDimensionIdentifier]);

        return $theseCoordinates === $otherCoordinates;
    }

    final public function hasCoordinate(Dimension\ContentDimensionIdentifier $dimensionIdentifier): bool
    {
        return isset($this->coordinates[(string)$dimensionIdentifier]);
    }

    final public function getCoordinate(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?string
    {
        return $this->coordinates[(string)$dimensionIdentifier] ?? null;
    }

    final public function equals(DimensionSpacePoint $other): bool
    {
        return $this === $other;
    }

    /**
     * @return array<string,array<int,string>>
     */
    final public function toLegacyDimensionArray(): array
    {
        $legacyDimensions = [];
        foreach ($this->coordinates as $dimensionName => $dimensionValue) {
            $legacyDimensions[$dimensionName] = [$dimensionValue];
        }

        return $legacyDimensions;
    }

    final public function serializeForUri(): string
    {
        return base64_encode(json_encode($this->coordinates));
    }

    /**
     * @return array<string,string>
     */
    final public function jsonSerialize(): array
    {
        return $this->coordinates;
    }

    final public function getCacheEntryIdentifier(): string
    {
        return $this->hash;
    }

    /**
     * @param string $methodName
     */
    final public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }

    final public function __toString(): string
    {
        return json_encode($this);
    }
}
