<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Routing\Exception;

use Neos\Neos\Routing\Exception;

/**
 * A "no workspace" exception
 */
class NoWorkspaceException extends Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 404;
}
