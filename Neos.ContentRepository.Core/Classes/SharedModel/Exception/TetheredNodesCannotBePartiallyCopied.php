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

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/**
 * The exception to be thrown if a tethered node aggregate with further whether nodes, determined by their ancestor is attempted to be copied.
 *
 * Only leaf tethered nodes can be copied.
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class TetheredNodesCannotBePartiallyCopied extends \DomainException
{
}
