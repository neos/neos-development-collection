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
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\Exception as Exceptions;

/**
 * Handles exceptions
 */
abstract class AbstractRenderingExceptionHandler {

	/**
	 * Current TypoScript runtime
	 *
	 * @var \TYPO3\TypoScript\Core\Runtime
	 */
	protected $runtime;

	/**
	 * Sets the current TypoScript runtime
	 *
	 * @param \TYPO3\TypoScript\Core\Runtime $runtime
	 * @return void
	 */
	public function setRuntime(Runtime $runtime) {
		$this->runtime = $runtime;
	}

	/**
	 * Returns current TypoScript runtime
	 *
	 * @return \TYPO3\TypoScript\Core\Runtime
	 */
	protected function getRuntime() {
		return $this->runtime;
	}

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
		if ($exception instanceof StopActionException) {
			throw $exception;
		}
		if ($exception instanceof Exceptions\RuntimeException) {
			$typoScriptPath = $exception->getTypoScriptPath();
			$exception = $exception->getPrevious();
		}
		$referenceCode = ($exception instanceof \TYPO3\Flow\Exception) ? $exception->getReferenceCode() : NULL;
		return $this->handle($typoScriptPath, $exception, $referenceCode);
	}

	/**
	 * Handles an Exception thrown while rendering TypoScript
	 *
	 * @param array $typoScriptPath path causing the exception
	 * @param \Exception $exception exception to handle
	 * @param integer $referenceCode
	 * @return string
	 */
	protected abstract function handle($typoScriptPath, \Exception $exception, $referenceCode);

	/**
	 * breaks the given path to multiple line to allow a nicer formatted logging
	 *
	 * example:
	 * formatScriptPath('page<Page>/body<Template>/content/main<ContentCollection>', ''):
	 * page<Page>/body<Template>/content/main<ContentCollection>
	 *
	 * formatScriptPath('page<Page>/body<Template>/content/main<ContentCollection>', '\n\t\t'):
	 * page<Page>/
	 *        body<Template>/
	 *        content/
	 *        main<ContentCollection>'
	 *
	 * @param $typoScriptPath path to format
	 * @param $delimiter path element delimiter
	 * @param bool $escapeHtml indicates whether to escape html-characters in the given path
	 * @return string
	 */
	protected function formatScriptPath($typoScriptPath, $delimiter, $escapeHtml = TRUE) {
		if ($escapeHtml) {
			$typoScriptPath = htmlspecialchars($typoScriptPath);
		}
		// TODO: hardcoded parsing?! where is the library for that
		$elements = explode('/', $typoScriptPath);

		return implode('/' . $delimiter, $elements);
	}
}
