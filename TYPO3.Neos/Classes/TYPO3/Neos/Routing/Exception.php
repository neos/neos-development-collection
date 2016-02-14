<?php
namespace TYPO3\Neos\Routing;

/*
 * This file is part of the TYPO3.Neos package.
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
class Exception extends \TYPO3\Neos\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 500;
}
