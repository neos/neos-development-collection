<?php
namespace TYPO3\Neos\TypoScript\ExceptionHandlers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;

/**
 * Provides a nicely formatted html error message
 * including all wrappers of an content element (i.e. menu allowing to
 * discard the broken element)
 */
class NodeWrappingHandler extends AbstractRenderingExceptionHandler {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * renders the exception to nice html content element to display, edit, remove, ...
	 *
	 * @param string $typoScriptPath - path causing the exception
	 * @param \Exception $exception - exception to handle
	 * @param integer $referenceCode - might be unset
	 * @return string
	 */
	protected function handle($typoScriptPath, \Exception $exception, $referenceCode) {
		$path = sprintf(
			'<div class="neos-typoscript-path"><div class="neos-typoscript-rootpath">%s</div></div>',
			$this->formatScriptPath($typoScriptPath, '</div><div class="neos-typoscript-subpath">')
		);
		$message = sprintf(
			'<div class="neos-exception-message">%s</div>',
			$this->getMessage($exception, $referenceCode)
		);
		$output = sprintf(
			'<div class="neos-rendering-exception"><div class="neos-rendering-exception-title">Failed to render element</div> %s %s</div>',
			$path,
			$message
		);

		$context = $this->getRuntime()->getCurrentContext();
		if (isset($context['node'])) {
			$node = $context['node'];
			return $this->contentElementWrappingService->wrapContentObject($node, $typoScriptPath, $output);
		}

		return $output;
	}

	/**
	 * appends the given reference code to the exception's message
	 * unless it is unset
	 *
	 * @param \Exception $exception
	 * @param $referenceCode
	 * @return string
	 */
	protected function getMessage(\Exception $exception, $referenceCode) {
		if (isset($referenceCode)) {
			return sprintf('%s (%s)', $exception->getMessage(), $referenceCode);
		}
		return $exception->getMessage();
	}
}
