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
 * The value object describing the weight of a variation edge between subgraphs A and B
 *
 * The weight defines a set of specialization depths by dimension identifier like
 * [
 *      'dimension1' => ContentDimensionValueSpecializationDepth(0),
 *      'dimension2' => ContentDimensionValueSpecializationDepth(3)
 * ],
 * which means that the value in dimension1 is the same in both subgraphs,
 * while subgraph B's value in dimension2 is a specialization of grade 3 of subgraph A's corresponding value.
 *
 * @see Dimension\ContentDimensionValueSpecializationDepth
 * @internal
 */
final readonly class ContentSubgraphVariationWeight implements \JsonSerializable
{
    public function __construct(
        /**
         * @var array<string,Dimension\ContentDimensionValueSpecializationDepth>
         */
        public array $value
    ) {
        foreach ($value as $dimensionId => $specializationDepth) {
            if (!$specializationDepth instanceof Dimension\ContentDimensionValueSpecializationDepth) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Weight component %s was not of type ContentDimensionValueSpecializationDepth',
                        get_debug_type($specializationDepth)
                    ),
                    1531477454
                );
            }
        }
    }

    public function getWeightInDimension(
        Dimension\ContentDimensionId $dimensionId
    ): ?Dimension\ContentDimensionValueSpecializationDepth {
        return $this->value[$dimensionId->value] ?? null;
    }

    public function canBeComparedTo(ContentSubgraphVariationWeight $other): bool
    {
        return array_keys($other->value) === array_keys($this->value);
    }

    /**
     * @throws Exception\ContentSubgraphVariationWeightsAreIncomparable
     */
    public function decreaseBy(ContentSubgraphVariationWeight $other): ContentSubgraphVariationWeight
    {
        if (!$this->canBeComparedTo($other)) {
            throw Exception\ContentSubgraphVariationWeightsAreIncomparable::butWereAttemptedTo($this, $other);
        }
        $decreasedWeight = [];
        foreach ($this->value as $rawDimensionId => $weight) {
            $dimensionId = new Dimension\ContentDimensionId($rawDimensionId);
            /**
             * @var Dimension\ContentDimensionValueSpecializationDepth $otherWeight
             * Null is already excluded by canBeComparedTo above
             */
            $otherWeight = $other->getWeightInDimension($dimensionId);
            $decreasedWeight[$rawDimensionId] = $weight->decreaseBy($otherWeight);
        }

        return new ContentSubgraphVariationWeight($decreasedWeight);
    }

    public function normalize(int $normalizationBase): int
    {
        $normalizedWeight = 0;
        $exponent = count($this->value) - 1;
        /** @var Dimension\ContentDimensionValueSpecializationDepth $specializationDepth */
        foreach ($this->value as $dimensionId => $specializationDepth) {
            $normalizedWeight += pow($normalizationBase, $exponent) * $specializationDepth->value;
            $exponent--;
        }

        return $normalizedWeight;
    }

    /**
     * @return array<string,Dimension\ContentDimensionValueSpecializationDepth>
     */
    public function jsonSerialize(): array
    {
        return $this->value;
    }

    /**
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
