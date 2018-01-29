<?php
namespace Neos\ContentRepository\Domain\Context\Dimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension\Exception\MissingContentDimensionDefaultValueException;
use Neos\Flow\Annotations as Flow;

/**
 * The configuration based content dimension source
 *
 * @Flow\Scope("singleton")
 */
final class ConfigurationBasedContentDimensionSource implements ContentDimensionSourceInterface
{
    const CONSTRAINT_IDENTIFIER_WILDCARD = '*';
    /**
     * @Flow\InjectConfiguration(path="contentDimensions")
     * @var array
     */
    protected $dimensionConfiguration;

    /**
     * @var array|ContentDimension[]
     */
    protected $contentDimensions;


    /**
     * @return void
     * @throws MissingContentDimensionDefaultValueException
     */
    protected function initializeDimensions()
    {
        if (!empty($this->dimensionConfiguration)) {
            foreach ($this->dimensionConfiguration as $rawDimensionIdentifier => $dimensionConfiguration) {
                $dimensionIdentifier = new ContentDimensionIdentifier($rawDimensionIdentifier);
                $values = [];
                $variationEdges = [];
                if (!isset($dimensionConfiguration['defaultValue'])) {
                    throw new MissingContentDimensionDefaultValueException('Content dimension ' . $rawDimensionIdentifier . ' has no default value defined.', 1516639042);
                }
                foreach ($dimensionConfiguration['values'] as $rawValue => $configuration) {
                    if (is_array($configuration)) {
                        $this->extractDimensionValuesAndVariations($rawValue, $values, $variationEdges, $configuration, null, new ContentDimensionValueSpecializationDepth(0));
                    }
                }
                if (!isset($values[$dimensionConfiguration['defaultValue']])) {
                    throw new MissingContentDimensionDefaultValueException('Content dimension ' . $rawDimensionIdentifier . ' has the undefined value ' . $dimensionConfiguration['defaultValue'] . ' declared as default value.', 1516639145);
                }

                $this->contentDimensions[$rawDimensionIdentifier] = new ContentDimension(
                    $dimensionIdentifier,
                    $values,
                    $values[$dimensionConfiguration['defaultValue']],
                    $variationEdges,
                    $dimensionConfiguration['configuration'] ?? []
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
     */
    protected function extractDimensionValuesAndVariations(string $rawValue, array & $values, array & $variationEdges, array $configuration, ?ContentDimensionValue $generalization, ContentDimensionValueSpecializationDepth $specializationDepth)
    {
        $constraints = [];
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
                $constraints[$rawDimensionIdentifier] = new ContentDimensionConstraints($wildcardAllowed, $identifierRestrictions);
            }
        }
        $value = new ContentDimensionValue($rawValue, $specializationDepth, $constraints);
        $values[$rawValue] = $value;
        if ($generalization) {
            $variationEdges[] = new ContentDimensionValueVariationEdge($value, $generalization);
        }

        if (isset($configuration['specializations'])) {
            foreach ($configuration['specializations'] as $rawSpecializationValue => $specializationConfiguration) {
                $this->extractDimensionValuesAndVariations($rawSpecializationValue, $values, $variationEdges, $specializationConfiguration, $value, $specializationDepth->increment());
            }
        }
    }

    /**
     * @param ContentDimensionIdentifier $dimensionIdentifier
     * @return ContentDimension|null
     * @throws MissingContentDimensionDefaultValueException
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
     * @throws MissingContentDimensionDefaultValueException
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        if (is_null($this->contentDimensions)) {
            $this->initializeDimensions();
        }

        return $this->contentDimensions;
    }
}