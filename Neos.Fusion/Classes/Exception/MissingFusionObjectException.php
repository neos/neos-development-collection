<?php
namespace Neos\Fusion\Exception;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Fusion\Exception;

/**
 * This exception is thrown if the the object type for a given Fusion path
 * could not be determined, f.e. if the user forgets to define "page = Page" in his
 * Fusion.
 */
class MissingFusionObjectException extends Exception
{
}
