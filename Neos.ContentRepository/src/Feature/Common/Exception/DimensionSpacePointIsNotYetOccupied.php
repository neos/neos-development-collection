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

use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a dimension space point is not yet occupied by a node in a node aggregate
 * but is supposed to be
 */
#[Flow\Proxy(false)]
final class DimensionSpacePointIsNotYetOccupied extends \DomainException
{
}
