<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Exception;

/**
 * The exception to be thrown if a dimension space point is tried to be used as a generalization of another one but isn't
 */
class DimensionSpacePointIsNoGeneralization extends Exception
{
}
