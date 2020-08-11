<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Exception;

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
 * The legacy Node API does not support the full TraversableNode API.
 */
class NodeMethodIsUnsupported extends Exception
{
}
