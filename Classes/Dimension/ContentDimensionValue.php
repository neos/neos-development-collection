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

use Neos\Utility\Arrays;

/**
 * A content dimension value in a single ContentDimension; e.g. the value "de" in the dimension "language".
 */
final class ContentDimensionValue
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var ContentDimensionValueSpecializationDepth
     */
    protected $specializationDepth;

    /**
     * @var array|ContentDimensionConstraints[]
     */
    protected $constraints;

    /**
     * General configuration like UI, detection etc.
     * @var array
     */
    protected $configuration;


    /**
     * @param string $value
     * @param ContentDimensionValueSpecializationDepth $specializationDepth
     * @param array|ContentDimensionConstraints[] $constraints
     * @param array $configuration
     * @throws Exception\ContentDimensionValueIsInvalid
     */
    public function __construct(
        string $value,
        ContentDimensionValueSpecializationDepth $specializationDepth = null,
        array $constraints = [],
        array $configuration = []
    ) {
        if (empty($value)) {
            throw new Exception\ContentDimensionValueIsInvalid('Content dimension values must not be empty.', 1516573481);
        }
        $this->value = $value;
        $this->specializationDepth = $specializationDepth ?: new ContentDimensionValueSpecializationDepth(0);
        $this->constraints = $constraints;
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return ContentDimensionValueSpecializationDepth
     */
    public function getSpecializationDepth(): ContentDimensionValueSpecializationDepth
    {
        return $this->specializationDepth;
    }

    /**
     * @return array|ContentDimensionConstraints[]
     */
    public function getAllConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @param ContentDimensionIdentifier $dimensionIdentifier
     * @return mixed|ContentDimensionConstraints|null
     */
    public function getConstraints(ContentDimensionIdentifier $dimensionIdentifier)
    {
        return $this->constraints[(string)$dimensionIdentifier] ?? null;
    }

    /**
     * @param ContentDimensionIdentifier $dimensionIdentifier
     * @param ContentDimensionValue $otherDimensionValue
     * @return bool
     */
    public function canBeCombinedWith(
        ContentDimensionIdentifier $dimensionIdentifier,
        ContentDimensionValue $otherDimensionValue
    ): bool {
        return isset($this->constraints[(string)$dimensionIdentifier])
            ? $this->constraints[(string)$dimensionIdentifier]->allowsCombinationWith($otherDimensionValue)
            : true;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getConfigurationValue(string $path)
    {
        return Arrays::getValueByPath($this->configuration, $path);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
