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

use Neos\ContentRepository\Core\Dimension\Exception\ContentDimensionValueIsInvalid;
use Neos\Utility\Arrays;

/**
 * A content dimension value in a single ContentDimension; e.g. the value "de" in the dimension "language".
 * @api
 */
final class ContentDimensionValue
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
    public function getConstraints(ContentDimensionId $dimensionId): ?ContentDimensionConstraints
    {
        return $this->constraints->getConstraints($dimensionId);
    }

    public function canBeCombinedWith(ContentDimensionId $dimensionId, ContentDimensionValue $otherDimensionValue): bool
    {
        return $this->constraints->allowsCombinationWith($dimensionId, $otherDimensionValue);
    }

    public function getConfigurationValue(string $path): mixed
    {
        $configuration = $this->configuration;

        return Arrays::getValueByPath($configuration, $path);
    }
}
