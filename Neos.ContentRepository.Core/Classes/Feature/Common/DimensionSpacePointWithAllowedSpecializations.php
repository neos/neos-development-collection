<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;

/**
 * A single dimension space point with allowed specializations
 *
 * @internal To be used in dimension space points for fallback constraint checks
 */
final readonly class DimensionSpacePointWithAllowedSpecializations
{
    /**
     * @param DimensionSpacePoint $dimensionSpacePoint The dimension space point to run constraint checks against
     * @param DimensionSpacePointSet $allowedSpecializations The set of dimension space points that should pass the constraint checks
     */
    private function __construct(
        public DimensionSpacePoint $dimensionSpacePoint,
        public DimensionSpacePointSet $allowedSpecializations,
    ) {
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint The dimension space point to run constraint checks against
     * @param DimensionSpacePointSet $allowedSpecializations The set of dimension space points that should pass the constraint checks
     */
    public static function create(
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionSpacePointSet $allowedSpecializations,
    ): self {
        return new self($dimensionSpacePoint, $allowedSpecializations);
    }
}
