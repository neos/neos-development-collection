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

namespace Neos\ContentRepository\Dimension;

use Neos\ContentRepository\Dimension\Exception\ContentDimensionIdentifierIsInvalid;
use Neos\ContentRepository\Dimension\Exception\ContentDimensionValueIsInvalid;
use Neos\ContentRepository\Dimension\Exception\ContentDimensionValuesAreInvalid;
use Neos\ContentRepository\Dimension\Exception\ContentDimensionValueSpecializationDepthIsInvalid;

/**
 * The configuration based content dimension source
 *
 * @api
 */
final class ConfigurationBasedContentDimensionSource implements ContentDimensionSourceInterface
{
    public const CONSTRAINT_IDENTIFIER_WILDCARD = '*';

    /**
     * Needs to stay protected as long as we need to be able to reset it via ObjectAccess
     * @var array<string,mixed>
     */
    protected array $dimensionConfiguration;

    /**
     * Needs to stay protected as long as we need to be able to reset it via ObjectAccess
     * @var array<string,ContentDimension>
     */
    protected ?array $contentDimensions = null;

    /**
     * @param array<string,mixed> $dimensionConfiguration
     */
    public function __construct(array $dimensionConfiguration)
    {
        $this->dimensionConfiguration = $dimensionConfiguration;
    }

    /**
     * @throws ContentDimensionIdentifierIsInvalid
     * @throws ContentDimensionValueIsInvalid
     * @throws ContentDimensionValueSpecializationDepthIsInvalid
     * @throws ContentDimensionValuesAreInvalid
     */
    protected function initializeDimensions(): void
    {
        if (!empty($this->dimensionConfiguration)) {
            $this->contentDimensions = [];
            foreach ($this->dimensionConfiguration as $rawDimensionIdentifier => $dimensionConfiguration) {
                $dimensionIdentifier = new ContentDimensionIdentifier($rawDimensionIdentifier);
                $values = [];
                $variationEdges = [];
                $additionalConfiguration = $dimensionConfiguration;

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
                unset($additionalConfiguration['values']);

                $this->contentDimensions[$rawDimensionIdentifier] = new ContentDimension(
                    $dimensionIdentifier,
                    new ContentDimensionValues($values),
                    new ContentDimensionValueVariationEdges($variationEdges),
                    $additionalConfiguration
                );
            }
        } else {
            $this->contentDimensions = [];
        }
    }

    /**
     * @param array<string,ContentDimensionValue> &$values
     * @param array<int,ContentDimensionValueVariationEdge> &$variationEdges
     * @param array<string,mixed> $configuration
     * @throws ContentDimensionValueIsInvalid
     */
    protected function extractDimensionValuesAndVariations(
        string $rawValue,
        array &$values,
        array &$variationEdges,
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
                if (!is_string($rawDimensionIdentifier)) {
                    throw new \InvalidArgumentException(
                        'Dimension combination constraints must be indexed by dimension name, '
                            . $rawDimensionIdentifier . ' given.'
                    );
                }
                foreach ($currentConstraints as $rawDimensionValue => $allowed) {
                    if (!is_string($rawDimensionValue)) {
                        throw new \InvalidArgumentException(
                            'Dimension value combination constraints must be indexed by dimension value, '
                                . $rawDimensionValue . ' given.'
                        );
                    }
                    if (!is_bool($allowed)) {
                        throw new \InvalidArgumentException(
                            'Dimension combination constraints must be boolean, '
                                . $allowed . ' given.'
                        );
                    }
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
                $this->extractDimensionValuesAndVariations(
                    $rawSpecializationValue,
                    $values,
                    $variationEdges,
                    $specializationConfiguration,
                    $value,
                    $specializationDepth->increment()
                );
            }
        }
    }

    /**
     * @throws ContentDimensionIdentifierIsInvalid
     * @throws ContentDimensionValueIsInvalid
     * @throws ContentDimensionValueSpecializationDepthIsInvalid
     * @throws ContentDimensionValuesAreInvalid
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
     * @throws ContentDimensionIdentifierIsInvalid
     * @throws ContentDimensionValueIsInvalid
     * @throws ContentDimensionValueSpecializationDepthIsInvalid
     * @throws ContentDimensionValuesAreInvalid
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        if (is_null($this->contentDimensions)) {
            $this->initializeDimensions();
        }
        /** @var array<string,ContentDimension> $contentDimensions */
        $contentDimensions = $this->contentDimensions;

        return $contentDimensions;
    }
}
