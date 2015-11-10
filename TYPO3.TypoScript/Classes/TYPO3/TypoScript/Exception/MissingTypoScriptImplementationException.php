<?php
namespace TYPO3\TypoScript\Exception;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * This exception is thrown if the PHP implementation class for a given TypoScript
 * object could not be found; i.e. it was not set using @class.
 */
class MissingTypoScriptImplementationException extends \TYPO3\TypoScript\Exception
{
}
