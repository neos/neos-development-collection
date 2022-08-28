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

/**
 * A directed edge connecting two dimension space points declaring them specialization and generalization
 *
 * @internal
 */
final class ContentDimensionValueVariationEdge
{
    public function __construct(
        public readonly ContentDimensionValue $specialization,
        public readonly ContentDimensionValue $generalization,
    ) {
    }
}
