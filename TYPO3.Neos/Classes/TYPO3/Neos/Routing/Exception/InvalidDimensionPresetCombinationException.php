<?php
namespace TYPO3\Neos\Routing\Exception;

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
 * An "invalid dimension preset combination" exception
 */
class InvalidDimensionPresetCombinationException extends \TYPO3\Neos\Routing\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 404;
}
