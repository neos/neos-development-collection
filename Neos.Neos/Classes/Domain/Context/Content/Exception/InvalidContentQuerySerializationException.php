<?php

namespace Neos\Neos\Domain\Context\Content\Exception;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;

/**
 * An exception to be thrown if a content query was not correctly serialized
 */
final class InvalidContentQuerySerializationException extends Exception
{
}
