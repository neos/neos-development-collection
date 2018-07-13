<?php

namespace Neos\ContentRepository\DimensionSpace\Dimension;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The configuration based content dimension source
 */
final class ConfigurationBasedContentDimensionSource implements ContentDimensionSourceInterface
{
    const CONSTRAINT_IDENTIFIER_WILDCARD = '*';

    /**
     * @var array
     */
    protected $dimensionConfiguration;

    /**
     * @var array|ContentDimension[]
     */
    protected $contentDimensions;

    public function __construct(array $dimensionConfiguration)
    {
        $this->dimensionConfiguration = $dimensionConfiguration;
    }

    /**
     * @return void
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     * @throws Exception\ContentDimensionValueIsInvalid
     * @throws Exception\ContentDimensionValueSpecializationDepthIsInvalid
     * @throws Exception\ContentDimensionValuesAreMissing
     * @throws Exception\ContentDimensionDefaultValueIsMissing
     */
    protected function initializeDimensions()
    {
        if (!empty($this->dimensionConfiguration)) {
            foreach ($this->dimensionConfiguration as $rawDimensionIdentifier => $dimensionConfiguration) {
                $dimensionIdentifier = new ContentDimensionIdentifier($rawDimensionIdentifier);
                $values = [];
                $variationEdges = [];
                $additionalConfiguration = $dimensionConfiguration;
                if (!isset($dimensionConfiguration['defaultValue'])) {
                    throw new Exception\ContentDimensionDefaultValueIsMissing('Content dimension ' . $rawDimensionIdentifier . ' has no default value defined.', 1516639042);
                }

                foreach ($dimensionConfiguration['values'] as $rawValue => $configuration) {
                    if (is_array($configuration)) {
                        $this->extractDimensionValuesAndVariations($rawValue, $values, $variationEdges, $configuration, null, new ContentDimensionValueSpecializationDepth(0));
                    }
                }
                if (!isset($values[$dimensionConfiguration['defaultValue']])) {
                    throw new Exception\ContentDimensionDefaultValueIsMissing('Content dimension ' . $rawDimensionIdentifier . ' has the undefined value ' . $dimensionConfiguration['defaultValue'] . ' declared as default value.', 1516639145);
                }
                unset($additionalConfiguration['values']);
                unset($additionalConfiguration['defaultValue']);

                $this->contentDimensions[$rawDimensionIdentifier] = new ContentDimension(
                    $dimensionIdentifier,
                    $values,
                    $values[$dimensionConfiguration['defaultValue']],
                    $variationEdges,
                    $additionalConfiguration
                );
            }
        } else {
            $this->contentDimensions = [];
        }
    }

    /**
     * @param string $rawValue
     * @param array $values
     * @param array $variationEdges
     * @param array $configuration
     * @param ContentDimensionValue|null $generalization
     * @param ContentDimensionValueSpecializationDepth $specializationDepth
     * @throws Exception\ContentDimensionValueIsInvalid
     */
    protected function extractDimensionValuesAndVariations(
        string $rawValue,
        array & $values,
        array & $variationEdges,
        array $configuration,
        ?ContentDimensionValue $generalization,
        ContentDimensionValueSpecializationDepth $specializationDepth
    ) {
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
                $constraints[$rawDimensionIdentifier] = new ContentDimensionConstraints($wildcardAllowed,
                    $identifierRestrictions);
            }
        }
        unset($additionalConfiguration['constraints']);
        unset($additionalConfiguration['specializations']);
        $value = new ContentDimensionValue($rawValue, $specializationDepth, $constraints, $additionalConfiguration);
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
     * @param ContentDimensionIdentifier $dimensionIdentifier
     * @return ContentDimension|null
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     * @throws Exception\ContentDimensionValueIsInvalid
     * @throws Exception\ContentDimensionValueSpecializationDepthIsInvalid
     * @throws Exception\ContentDimensionValuesAreMissing
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
     * @return array|ContentDimension[]
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     * @throws Exception\ContentDimensionValueIsInvalid
     * @throws Exception\ContentDimensionValueSpecializationDepthIsInvalid
     * @throws Exception\ContentDimensionValuesAreMissing
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
