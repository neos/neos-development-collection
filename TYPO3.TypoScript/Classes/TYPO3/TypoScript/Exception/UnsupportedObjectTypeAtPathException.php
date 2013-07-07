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
 * This exception is thrown if a TypoScript path needs to contain exactly a specific
 * object type; f.e. a "Case" TypoScript object expects all their children being
 * TypoScript objects and does not support Eel Expressions or simple objects.
 */
class UnsupportedObjectTypeAtPathException extends \TYPO3\TypoScript\Exception {

}
?>