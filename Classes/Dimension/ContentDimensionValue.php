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

use Neos\ContentRepository\DimensionSpace\Dimension\Exception\ContentDimensionValueIsInvalid;
use Neos\Utility\Arrays;

/**
 * A content dimension value in a single ContentDimension; e.g. the value "de" in the dimension "language".
 */
final class ContentDimensionValue
{
    public readonly string $value;

    public readonly ContentDimensionValueSpecializationDepth $specializationDepth;

    public readonly ContentDimensionConstraintSet $constraints;

    /**
     * General configuration like UI, detection etc.
     *
     * @var array<string,mixed>
     */
    public readonly array $configuration;

    /**
     * @param array<string,mixed> $configuration
     * @throws ContentDimensionValueIsInvalid
     */
    public function __construct(
        string $value,
        ContentDimensionValueSpecializationDepth $specializationDepth = null,
        ContentDimensionConstraintSet $constraints = null,
        array $configuration = []
    ) {
        if (empty($value)) {
            throw ContentDimensionValueIsInvalid::becauseItMustNoteBeEmpty();
        }
        $this->value = $value;
        $this->specializationDepth = $specializationDepth ?: new ContentDimensionValueSpecializationDepth(0);
        $this->constraints = $constraints ?: ContentDimensionConstraintSet::createEmpty();
        $this->configuration = $configuration;
    }

    public function getConstraints(ContentDimensionIdentifier $dimensionIdentifier): ?ContentDimensionConstraints
    {
        return $this->constraints->getConstraints($dimensionIdentifier);
    }

    public function canBeCombinedWith(
        ContentDimensionIdentifier $dimensionIdentifier,
        ContentDimensionValue $otherDimensionValue
    ): bool {
        return $this->constraints->allowsCombinationWith(
            $dimensionIdentifier,
            $otherDimensionValue
        );
    }

    public function getConfigurationValue(string $path): mixed
    {
        $configuration = $this->configuration;

        return Arrays::getValueByPath($configuration, $path);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
