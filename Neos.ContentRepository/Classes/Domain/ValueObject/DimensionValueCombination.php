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
use Neos\Flow\Annotations as Flow;

final class DimensionValueCombination implements \JsonSerializable
{
    /**
     * @var array|DimensionValue[]
     */
    protected $dimensionValues;


    public function __construct(array $dimensionValues)
    {
        $this->dimensionValues = $dimensionValues;
    }

    public static function fromLegacyDimensionArray(array $legacyDimensionValues): DimensionValueCombination
    {
        $dimensionValues = [];
        foreach ($legacyDimensionValues as $dimensionName => $rawDimensionValues) {
            $dimensionValues[$dimensionName] = new DimensionValue(reset($rawDimensionValues));
        }

        return new DimensionValueCombination($dimensionValues);
    }


    public function equals(DimensionValueCombination $otherDimensionCombination)
    {
        return $this->toArray() === $otherDimensionCombination->toArray();
    }

    /**
     * @return array|DimensionValue[]
     */
    public function getDimensionValues(): array
    {
        return $this->dimensionValues;
    }

    public function toArray(): array
    {
        return $this->dimensionValues;
    }

    function jsonSerialize(): array
    {
        return $this->dimensionValues;
    }
}
