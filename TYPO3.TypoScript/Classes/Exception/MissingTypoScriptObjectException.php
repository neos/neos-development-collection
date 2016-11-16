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
use TYPO3\TypoScript\Exception;

/**
 * This exception is thrown if the the object type for a given TypoScript path
 * could not be determined, f.e. if the user forgets to define "page = Page" in his
 * TypoScript.
 */
class MissingTypoScriptObjectException extends Exception
{
}
