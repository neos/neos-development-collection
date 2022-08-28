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
 * The exception to be thrown if a dimension space point is tried to be used
 * as a specialization of another one but isn't
 * @api
 */
class DimensionSpacePointIsNoSpecialization extends \DomainException
{
    public static function butWasSupposedToBe(DimensionSpacePoint $target, DimensionSpacePoint $source): self
    {
        return new self(sprintf(
            'The Dimension space point %s is no specialization of %s.',
            $target,
            $source
        ), 1617275140);
    }
}
