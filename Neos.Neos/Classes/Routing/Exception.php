<?php
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A TYPO3 routing exception
 */
class Exception extends \Neos\Neos\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 500;
}
