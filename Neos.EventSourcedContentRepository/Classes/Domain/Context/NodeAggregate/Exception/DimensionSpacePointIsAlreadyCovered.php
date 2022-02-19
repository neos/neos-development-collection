<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a dimension space point is already covered by a node aggregate
 * but is supposed not to be
 */
#[Flow\Proxy(false)]
final class DimensionSpacePointIsAlreadyCovered extends \DomainException
{
}
