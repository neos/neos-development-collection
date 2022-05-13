<?php

namespace Neos\ContentRepository\Feature\Common\Exception;

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
 * The exception to be thrown if a requested node aggregate does already exist
 */
#[Flow\Proxy(false)]
final class NodeAggregateCurrentlyExists extends \DomainException
{
}
