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

use Neos\ContentRepository\Domain\Context\Dimension\Exception\MissingContentDimensionValuesException;
use Neos\Utility\Arrays;

/**
 * The content dimension domain model
 */
final class ContentDimension
{
    /**
     * @var ContentDimensionIdentifier
     */
    protected $identifier;

    /**
     * An array of dimension values, indexed by their identifiers
     *
     * @var array|ContentDimensionValue[]
     */
    protected $values = [];

    /**
     * all Content Dimension Values indexed by "specialization", so
     * you can answer questions like "what's the next-most generic value for the given value"
     *
     * @var array|ContentDimensionValue[]
     */
    protected $generalizations;

    /**
     * all Content Dimension Values indexed by "generalization", so
     * you can answer questions like "what are the next-most specialized values for the given value"
     *
     * returns an *array* of specializations for each key; so this is effectively an array of arrays
     *
     * @var array|ContentDimensionValue[][]
     */
    protected $specializations;

    /**
     * @var ContentDimensionValue
     */
    protected $defaultValue;

    /**
     * General configuration like UI, detection etc.
     * @var array
     */
    protected $configuration;

    /**
     * @var ContentDimensionValueSpecializationDepth
     */
    protected $maximumDepth;


    /**
     * @param ContentDimensionIdentifier $identifier
     * @param array|ContentDimensionValue[] $values
     * @param ContentDimensionValue $defaultValue
     * @param array|ContentDimensionValueVariationEdge[] $variationEdges
     * @param array $configuration
     * @throws MissingContentDimensionValuesException
     */
    public function __construct(ContentDimensionIdentifier $identifier, array $values, ContentDimensionValue $defaultValue, array $variationEdges = [], array $configuration = [])
    {
        if (empty($values)) {
            throw new MissingContentDimensionValuesException('Content dimension ' . $identifier . ' does not have any values defined', 1516576422);
        }
        $this->identifier = $identifier;
        foreach ($values as $value) {
            $this->values[(string)$value] = $value;
            if (is_null($this->maximumDepth) || $value->getSpecializationDepth()->isGreaterThan($this->maximumDepth)) {
                $this->maximumDepth = $value->getSpecializationDepth();
            }
        }
        $this->defaultValue = $defaultValue;
        if (!empty($variationEdges)) {
            foreach ($variationEdges as $variationEdge) {
                $this->generalizations[(string)$variationEdge->getSpecialization()] = $variationEdge->getGeneralization();
                $this->specializations[(string)$variationEdge->getGeneralization()][(string)$variationEdge->getSpecialization()] = $variationEdge->getSpecialization();
            }
        }
        $this->configuration = $configuration;
    }


    /**
     * @return ContentDimensionIdentifier
     */
    public function getIdentifier(): ContentDimensionIdentifier
    {
        return $this->identifier;
    }

    /**
     * @return array|ContentDimensionValue[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param string $value
     * @return ContentDimensionValue|null
     */
    public function getValue(string $value): ?ContentDimensionValue
    {
        return $this->values[$value] ?? null;
    }

    /**
     * @return ContentDimensionValue
     */
    public function getDefaultValue(): ContentDimensionValue
    {
        return $this->defaultValue;
    }

    /**
     * @return array|ContentDimensionValue[]
     */
    public function getRootValues(): array
    {
        return array_filter($this->values, function (ContentDimensionValue $dimensionValue) {
            return $dimensionValue->getSpecializationDepth()->getDepth() === 0;
        });
    }

    /**
     * @param ContentDimensionValue $dimensionValue
     * @return ContentDimensionValue|null
     */
    public function getGeneralization(ContentDimensionValue $dimensionValue): ?ContentDimensionValue
    {
        return $this->generalizations[(string)$dimensionValue] ?? null;
    }

    /**
     * @param ContentDimensionValue $dimensionValue
     * @return array|ContentDimensionValue[]
     */
    public function getSpecializations(ContentDimensionValue $dimensionValue): array
    {
        return $this->specializations[(string)$dimensionValue] ?? [];
    }

    /**
     * @param ContentDimensionValue $dimensionValue
     * @param callable $callback
     */
    public function traverseGeneralizations(ContentDimensionValue $dimensionValue, callable $callback)
    {
        $callback($dimensionValue);
        if ($this->getGeneralization($dimensionValue)) {
            $this->traverseGeneralizations($this->getGeneralization($dimensionValue), $callback);
        }
    }

    /**
     * @param ContentDimensionValue $generalization
     * @param ContentDimensionValue $specialization
     * @return ContentDimensionValueSpecializationDepth
     * @throws Exception\GeneralizationIsInvalid
     */
    public function calculateSpecializationDepth(ContentDimensionValue $specialization, ContentDimensionValue $generalization): ContentDimensionValueSpecializationDepth
    {
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

        throw new Exception\GeneralizationIsInvalid('"' . $specialization . '" is no specialization of "' . $generalization . '" in dimension "' . $this->getIdentifier() . '".');
    }

    /**
     * @return ContentDimensionValueSpecializationDepth
     */
    public function getMaximumDepth(): ContentDimensionValueSpecializationDepth
    {
        return $this->maximumDepth;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getConfigurationValue(string $path)
    {
        return Arrays::getValueByPath($this->configuration, $path);
    }
}
