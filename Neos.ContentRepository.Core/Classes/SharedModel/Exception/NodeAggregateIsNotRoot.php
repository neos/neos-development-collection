<?php

namespace Neos\ContentRepository\Core\SharedModel\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * The exception to be thrown if a node aggregate is classified *NOT* as root but was expected to be
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class NodeAggregateIsNotRoot extends \DomainException
{
}
