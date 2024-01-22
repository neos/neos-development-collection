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

namespace Neos\ContentRepository\Core\DimensionSpace;

use Neos\ContentRepository\Core\Dimension;

/**
 * A point in the dimension space with coordinates DimensionName => DimensionValue.
 * E.g.: ["language" => "es", "country" => "ar"]
 *
 * @api
 */
abstract class AbstractDimensionSpacePoint implements \JsonSerializable
{
    protected function __construct(
        /**
         * @var array<string,string>
         */
        public readonly array $coordinates,
        public readonly string $hash
    ) {
    }

    /**
     * @param array<string,string> $coordinates
     */
    final protected static function hashCoordinates(array $coordinates): string
    {
        $identityComponents = $coordinates;
        ksort($identityComponents);

        return md5(json_encode($identityComponents, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string,string> $coordinates
     */
    final protected static function validateCoordinates(array $coordinates): void
    {
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
                    sprintf(
                        'Dimension value for %s is not a string but: %s',
                        $dimensionName,
                        get_debug_type($dimensionValue)
                    ),
                    1506076562
                );
            }
            if ($dimensionValue === '') {
                throw new \InvalidArgumentException('Dimension value must not be empty', 1506076563);
            }
        }
    }

    /**
     * A variant VarA is a "Direct Variant in Dimension Dim" of another variant VarB,
     * if VarA and VarB are sharing all dimension values except in "Dim",
     * AND they have differing dimension values in "Dim". Thus, VarA and VarB only vary in the given "Dim".
     * It does not say anything about how VarA and VarB relate (if it is specialization, peer or generalization).
     */
    final public function isDirectVariantInDimension(
        self $other,
        Dimension\ContentDimensionId $contentDimensionId
    ): bool {
        if (!$this->hasCoordinate($contentDimensionId) || !$other->hasCoordinate($contentDimensionId)) {
            return false;
        }
        if (
            $this->coordinates[$contentDimensionId->value]
            === $other->coordinates[$contentDimensionId->value]
        ) {
            return false;
        }

        $theseCoordinates = $this->coordinates;
        $otherCoordinates = $other->coordinates;
        unset($theseCoordinates[$contentDimensionId->value]);
        unset($otherCoordinates[$contentDimensionId->value]);

        return $theseCoordinates === $otherCoordinates;
    }

    final public function hasCoordinate(Dimension\ContentDimensionId $dimensionId): bool
    {
        return isset($this->coordinates[$dimensionId->value]);
    }

    final public function getCoordinate(Dimension\ContentDimensionId $dimensionId): ?string
    {
        return $this->coordinates[$dimensionId->value] ?? null;
    }

    /**
     * Equals check (as opposed to === same check, which is usually the preferred variant)
     * Compares two hashes, since the DSPs themselves might be of different classes
     */
    final public function equals(self $other): bool
    {
        return $this->hash === $other->hash;
    }

    /**
     * @return array<string,array<int,string>>
     * @deprecated should be only used for conversion from Neos <= 8.x to 9.x upwards. never use this in "modern" code.
     */
    final public function toLegacyDimensionArray(): array
    {
        $legacyDimensions = [];
        foreach ($this->coordinates as $dimensionName => $dimensionValue) {
            $legacyDimensions[$dimensionName] = [$dimensionValue];
        }

        return $legacyDimensions;
    }


    /**
     * @return array<string,string>
     */
    final public function jsonSerialize(): array
    {
        return $this->coordinates;
    }

    /**
     * @throws \JsonException
     */
    final public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
