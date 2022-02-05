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

namespace Neos\ContentRepository\DimensionSpace\Dimension;

use Neos\ContentRepository\Domain\Model\InterDimension\VariationEdge;

/**
 * The configuration based content dimension source
 */
final class ConfigurationBasedContentDimensionSource implements ContentDimensionSourceInterface
{
    const CONSTRAINT_IDENTIFIER_WILDCARD = '*';

    /**
     * @var array<string,mixed>
     */
    private array $dimensionConfiguration;

    /**
     * @var array<string,ContentDimension>
     */
    private ?array $contentDimensions = null;

    /**
     * @param array<string,mixed> $dimensionConfiguration
     */
    public function __construct(array $dimensionConfiguration)
    {
        $this->dimensionConfiguration = $dimensionConfiguration;
    }

    /**
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     * @throws Exception\ContentDimensionValueIsInvalid
     * @throws Exception\ContentDimensionValueSpecializationDepthIsInvalid
     * @throws Exception\ContentDimensionValuesAreInvalid
     * @throws Exception\ContentDimensionDefaultValueIsMissing
     */
    protected function initializeDimensions(): void
    {
        if (!empty($this->dimensionConfiguration)) {
            foreach ($this->dimensionConfiguration as $rawDimensionIdentifier => $dimensionConfiguration) {
                $dimensionIdentifier = new ContentDimensionIdentifier($rawDimensionIdentifier);
                $values = [];
                $variationEdges = [];
                $additionalConfiguration = $dimensionConfiguration;
                if (!isset($dimensionConfiguration['defaultValue'])) {
                    throw Exception\ContentDimensionDefaultValueIsMissing::becauseItIsUndeclared($dimensionIdentifier);
                }

                foreach ($dimensionConfiguration['values'] as $rawValue => $configuration) {
                    if (is_array($configuration)) {
                        $this->extractDimensionValuesAndVariations(
                            $rawValue,
                            $values,
                            $variationEdges,
                            $configuration,
                            null,
                            new ContentDimensionValueSpecializationDepth(0)
                        );
                    }
                }
                if (!isset($values[$dimensionConfiguration['defaultValue']])) {
                    throw Exception\ContentDimensionDefaultValueIsMissing::becauseItsDeclaredValueIsUndefined(
                        $dimensionIdentifier,
                        new ContentDimensionValue($dimensionConfiguration['defaultValue'])
                    );
                }
                unset($additionalConfiguration['values']);
                unset($additionalConfiguration['defaultValue']);

                $this->contentDimensions[$rawDimensionIdentifier] = new ContentDimension(
                    $dimensionIdentifier,
                    new ContentDimensionValues($values),
                    $values[$dimensionConfiguration['defaultValue']],
                    new ContentDimensionValueVariationEdges($variationEdges),
                    $additionalConfiguration
                );
            }
        } else {
            $this->contentDimensions = [];
        }
    }

    /**
     * @param array<string,ContentDimensionValue> $values
     * @param array<int,VariationEdge> $variationEdges
     * @param array<string,mixed> $configuration
     * @throws Exception\ContentDimensionValueIsInvalid
     */
    protected function extractDimensionValuesAndVariations(
        string $rawValue,
        array& $values,
        array& $variationEdges,
        array $configuration,
        ?ContentDimensionValue $generalization,
        ContentDimensionValueSpecializationDepth $specializationDepth
    ): void {
        $constraints = [];
        $additionalConfiguration = $configuration;
        if (isset($configuration['constraints'])) {
            foreach ($configuration['constraints'] as $rawDimensionIdentifier => $currentConstraints) {
                $wildcardAllowed = true;
                $identifierRestrictions = [];
                foreach ($currentConstraints as $rawDimensionValue => $allowed) {
                    if ($rawDimensionValue === self::CONSTRAINT_IDENTIFIER_WILDCARD) {
                        $wildcardAllowed = $allowed;
                    } else {
                        $identifierRestrictions[$rawDimensionValue] = $allowed;
                    }
                }
                $constraints[$rawDimensionIdentifier] = new ContentDimensionConstraints(
                    $wildcardAllowed,
                    $identifierRestrictions
                );
            }
        }
        unset($additionalConfiguration['constraints']);
        unset($additionalConfiguration['specializations']);
        $value = new ContentDimensionValue(
            $rawValue,
            $specializationDepth,
            new ContentDimensionConstraintSet($constraints),
            $additionalConfiguration
        );
        $values[$rawValue] = $value;
        if ($generalization) {
            $variationEdges[] = new ContentDimensionValueVariationEdge($value, $generalization);
        }

        if (isset($configuration['specializations'])) {
            foreach ($configuration['specializations'] as $rawSpecializationValue => $specializationConfiguration) {
                $this->extractDimensionValuesAndVariations($rawSpecializationValue, $values, $variationEdges,
                    $specializationConfiguration, $value, $specializationDepth->increment());
            }
        }
    }

    /**
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     * @throws Exception\ContentDimensionValueIsInvalid
     * @throws Exception\ContentDimensionValueSpecializationDepthIsInvalid
     * @throws Exception\ContentDimensionValuesAreInvalid
     * @throws Exception\ContentDimensionDefaultValueIsMissing
     */
    public function getDimension(ContentDimensionIdentifier $dimensionIdentifier): ?ContentDimension
    {
        if (is_null($this->contentDimensions)) {
            $this->initializeDimensions();
        }

        return $this->contentDimensions[(string)$dimensionIdentifier] ?? null;
    }

    /**
     * @return array<string,ContentDimension>
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     * @throws Exception\ContentDimensionValueIsInvalid
     * @throws Exception\ContentDimensionValueSpecializationDepthIsInvalid
     * @throws Exception\ContentDimensionValuesAreInvalid
     * @throws Exception\ContentDimensionDefaultValueIsMissing
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        if (is_null($this->contentDimensions)) {
            $this->initializeDimensions();
        }

        return $this->contentDimensions;
    }
}
