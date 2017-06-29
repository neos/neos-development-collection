<?php
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
 * An paginator exception saying "Page not Found"
 *
 */
class PageNotFoundException extends Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 404;
}
