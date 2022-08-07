<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Common\Exception;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a dimension space point is not the primary generalization of another one.
 */
#[Flow\Proxy(false)]
final class DimensionSpacePointHasNoPrimaryGeneralization extends \DomainException
{
    public static function butWasSupposedToHave(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            'Dimension space point ' . json_encode($dimensionSpacePoint)
                . ' has no primary generalization but was supposed to have.',
            1659618199
        );
    }
}
