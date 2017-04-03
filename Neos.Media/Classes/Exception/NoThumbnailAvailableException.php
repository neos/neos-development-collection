<?php
namespace Neos\Media\Exception;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Exception;

/**
 * A Neos.Media exception for the thumbnail service if the given asset is not able to generate a thumbnail.
 *
 * @api
 */
class NoThumbnailAvailableException extends Exception
{
}
