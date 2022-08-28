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

namespace Neos\ContentRepository\DimensionSpace;

use Neos\ContentRepository\Dimension;

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
final class ContentSubgraphVariationWeight implements \JsonSerializable, \Stringable
{
    public function __construct(
        /**
         * @var array<string,Dimension\ContentDimensionValueSpecializationDepth>
         */
        public readonly array $weight
    ) {
        foreach ($weight as $dimensionIdentifier => $specializationDepth) {
            if (!$specializationDepth instanceof Dimension\ContentDimensionValueSpecializationDepth) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Weight component %s was not of type ContentDimensionValueSpecializationDepth',
                        $specializationDepth
                    ),
                    1531477454
                );
            }
        }
    }

    public function getWeightInDimension(
        Dimension\ContentDimensionIdentifier $dimensionIdentifier
    ): ?Dimension\ContentDimensionValueSpecializationDepth {
        return $this->weight[(string)$dimensionIdentifier] ?? null;
    }

    public function canBeComparedTo(ContentSubgraphVariationWeight $other): bool
    {
        return array_keys($other->weight) === array_keys($this->weight);
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
        foreach ($this->weight as $rawDimensionIdentifier => $weight) {
            $dimensionIdentifier = new Dimension\ContentDimensionIdentifier($rawDimensionIdentifier);
            /**
             * @var Dimension\ContentDimensionValueSpecializationDepth $otherWeight
             * Null is already excluded by canBeComparedTo above
             */
            $otherWeight = $other->getWeightInDimension($dimensionIdentifier);
            $decreasedWeight[$rawDimensionIdentifier] = $weight->decreaseBy($otherWeight);
        }

        return new ContentSubgraphVariationWeight($decreasedWeight);
    }

    public function normalize(int $normalizationBase): int
    {
        $normalizedWeight = 0;
        $exponent = count($this->weight) - 1;
        foreach ($this->weight as $dimensionIdentifier => $specializationDepth) {
            $normalizedWeight += pow($normalizationBase, $exponent) * $specializationDepth->depth;
            $exponent--;
        }

        return $normalizedWeight;
    }

    /**
     * @return array<string,Dimension\ContentDimensionValueSpecializationDepth>
     */
    public function jsonSerialize(): array
    {
        return $this->weight;
    }

    /**
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
