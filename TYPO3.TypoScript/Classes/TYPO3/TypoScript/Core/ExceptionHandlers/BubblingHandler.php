<?php
namespace TYPO3\TypoScript\Core\ExceptionHandlers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\Exception\RuntimeException;

/**
 * Wrap the exception to retain the typoScript path at which it was originally thrown
 */
class BubblingHandler extends AbstractRenderingExceptionHandler {

	/**
	 * Handle an Exception thrown while rendering TypoScript
	 *
	 * @param array $typoScriptPath
	 * @param \Exception $exception
	 * @return string
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
	 * @throws \Exception
	 */
	public function handleRenderingException($typoScriptPath, \Exception $exception) {
		if ($exception instanceof RuntimeException) {
			throw $exception;
		} else {
			throw new RuntimeException('TypoScript Rendering Exception, see typoScriptPath and nested Exception for details.', 1401803055, $exception, $typoScriptPath);
		}
	}

	/**
	 * Handles an Exception thrown while rendering TypoScript
	 *
	 * @param string $typoScriptPath path causing the exception
	 * @param \Exception $exception exception to handle
	 * @param integer $referenceCode
	 * @return void
	 */
	protected function handle($typoScriptPath, \Exception $exception, $referenceCode) {
		// nothing to be done here, as this method is normally called in "handleRenderingException()", which was overridden above.
	}

}
