<?php
namespace Neos\ContentRepository\Domain\Context\Dimension\Exception;

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
 * The exception to be thrown if an invalid generalization of a content dimension value was tried to be initialized
 */
class GeneralizationIsInvalid extends Exception
{
}
