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

namespace Neos\ContentRepository\DimensionSpace\Exception;

use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;

/**
 * A dimension space point was not found
 * @api
 */
class DimensionSpacePointNotFound extends \DomainException
{
    public static function becauseItIsNotWithinTheAllowedDimensionSubspace(
        DimensionSpacePoint $dimensionSpacePoint
    ): self {
        return new self(
            sprintf('%s was not found in the allowed dimension subspace', $dimensionSpacePoint),
            1505929456
        );
    }
}
