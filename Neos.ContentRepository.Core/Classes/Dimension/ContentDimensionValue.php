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

use Neos\ContentRepository\Dimension\Exception\ContentDimensionValueIsInvalid;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * A content dimension value in a single ContentDimension; e.g. the value "de" in the dimension "language".
 * @api
 */
final class ContentDimensionValue implements \Stringable
{
    /**
     * @throws ContentDimensionValueIsInvalid
     */
    public function __construct(
        public readonly string $value,
        /** @codingStandardsIgnoreStart */
        public readonly ContentDimensionValueSpecializationDepth $specializationDepth = new ContentDimensionValueSpecializationDepth(0),
        /** @codingStandardsIgnoreEnd */
        public readonly ContentDimensionConstraintSet $constraints = new ContentDimensionConstraintSet([]),
        /**
         * General configuration like UI, detection etc.
         * @var array<string,mixed>
         */
        public readonly array $configuration = []
    ) {
        if (empty($value)) {
            throw ContentDimensionValueIsInvalid::becauseItMustNotBeEmpty();
        }
    }

    /**
     * @internal
     * @deprecated unused
     */
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
