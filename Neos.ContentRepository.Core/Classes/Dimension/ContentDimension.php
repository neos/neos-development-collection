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

namespace Neos\ContentRepository\Core\Dimension;

use Neos\Utility\Arrays;

/**
 * The content dimension domain model
 *
 * @api
 */
final readonly class ContentDimension
{
    /**
     * all Content Dimension Values indexed by "specialization", so
     * you can answer questions like "what's the next-most generic value for the given value"
     *
     * @var array<string,ContentDimensionValue>
     */
    private array $generalizations;

    /**
     * all Content Dimension Values indexed by "generalization", so
     * you can answer questions like "what are the next-most specialized values for the given value"
     *
     * returns an *array* of specializations for each key; so this is effectively an array of arrays
     *
     * @var array<string,array<string,ContentDimensionValue>>
     * @internal
     */
    private array $specializations;

    /**
     * @param array<string,mixed> $configuration
     * @internal
     */
    public function __construct(
        public ContentDimensionId $id,
        public ContentDimensionValues $values,
        ContentDimensionValueVariationEdges $variationEdges,
        /** General configuration like UI, detection etc. */
        public array $configuration = []
    ) {
        $generalizations = [];
        $specializations = [];
        foreach ($variationEdges as $variationEdge) {
            $generalizations[$variationEdge->specialization->value] = $variationEdge->generalization;
            $specializations[$variationEdge->generalization->value][$variationEdge->specialization->value]
                = $variationEdge->specialization;
        }
        $this->generalizations = $generalizations;
        $this->specializations = $specializations;
    }

    /**
     * @api
     */
    public function getValue(string $value): ?ContentDimensionValue
    {
        return $this->values->getValue($value);
    }

    /**
     * @return array<string,ContentDimensionValue>
     * @api
     */
    public function getRootValues(): array
    {
        return $this->values->getRootValues();
    }


    public function getGeneralization(ContentDimensionValue $dimensionValue): ?ContentDimensionValue
    {
        return $this->generalizations[$dimensionValue->value] ?? null;
    }

    /**
     * @return array<string,ContentDimensionValue>
     */
    public function getSpecializations(ContentDimensionValue $dimensionValue): array
    {
        return $this->specializations[$dimensionValue->value] ?? [];
    }

    /**
     * @deprecated not used - shall we remove it?
     * @internal
     */
    public function traverseGeneralizations(ContentDimensionValue $dimensionValue, callable $callback): void
    {
        $callback($dimensionValue);
        if ($this->getGeneralization($dimensionValue)) {
            $this->traverseGeneralizations($this->getGeneralization($dimensionValue), $callback);
        }
    }

    /**
     * @deprecated not used - shall we remove it?
     * @internal
     */
    public function calculateSpecializationDepth(
        ContentDimensionValue $specialization,
        ContentDimensionValue $generalization
    ): ContentDimensionValueSpecializationDepth {
        $specializationDepth = 0;
        $currentGeneralization = $specialization;

        while ($currentGeneralization) {
            if ($currentGeneralization === $generalization) {
                return new ContentDimensionValueSpecializationDepth($specializationDepth);
            } else {
                $currentGeneralization = $this->getGeneralization($currentGeneralization);
                $specializationDepth++;
            }
        }

        throw Exception\GeneralizationIsInvalid::becauseComparedValueIsNoSpecialization(
            $generalization,
            $specialization,
            $this->id
        );
    }

    public function getConfigurationValue(string $path): mixed
    {
        $configuration = $this->configuration;

        return Arrays::getValueByPath($configuration, $path);
    }

    /**
     * @deprecated not used - shall we remove it?
     * @internal
     */
    public function getMaximumDepth(): ContentDimensionValueSpecializationDepth
    {
        return $this->values->maximumDepth;
    }
}
