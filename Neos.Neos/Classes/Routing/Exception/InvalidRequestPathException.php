<?php
namespace Neos\Neos\Routing\Exception;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Neos\Routing\Exception;

/**
 * An "invalid request path" exception
 */
class InvalidRequestPathException extends Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 404;
}
