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

/**
 * Just rethrows the given exception
 */
class ThrowingHandler extends AbstractRenderingExceptionHandler {

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
		throw $exception;
	}

	/**
	 * Handles an Exception thrown while rendering TypoScript
	 *
	 * @param string $typoScriptPath path causing the exception
	 * @param \Exception $exception exception to handle
	 * @param integer $referenceCode
	 * @return string
	 */
	protected function handle($typoScriptPath, \Exception $exception, $referenceCode) {

	}

}
