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

/**
 * The exception to be thrown if a tethered node aggregate is attempted to be removed
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class TetheredNodeAggregateCannotBeRemoved extends \DomainException
{
}
