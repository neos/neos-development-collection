<?php
namespace TYPO3\TypoScript\Exception;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This exception is thrown if the PHP implementation class for a given TypoScript
 * object could not be found; i.e. it was not set using @class.
 */
class MissingTypoScriptImplementationException extends \TYPO3\TypoScript\Exception
{
}
