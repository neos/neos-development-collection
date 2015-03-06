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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Utility\Environment;
use TYPO3\Neos\Service\ContentElementWrappingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use TYPO3\TypoScript\Core\ExceptionHandlers\ContextDependentHandler;

/**
 * Provides a nicely formatted html error message
 * including all wrappers of an content element (i.e. menu allowing to
 * discard the broken element)
 */
class NodeWrappingHandler extends AbstractRenderingExceptionHandler {

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * @Flow\Inject
	 * @var Environment
	 */
	protected $environment;

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * renders the exception to nice html content element to display, edit, remove, ...
	 *
	 * @param string $typoScriptPath - path causing the exception
	 * @param \Exception $exception - exception to handle
	 * @param integer $referenceCode - might be unset
	 * @return string
	 */
	protected function handle($typoScriptPath, \Exception $exception, $referenceCode) {
		$handler = new ContextDependentHandler();
		$handler->setRuntime($this->runtime);
		$output = $handler->handleRenderingException($typoScriptPath, $exception);

		$currentContext = $this->getRuntime()->getCurrentContext();
		if (isset($currentContext['node'])) {
			$context = $this->environment->getContext();
			/** @var NodeInterface $siteNode */
			$siteNode = $currentContext['site'];
			if ($context->isProduction() && $this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess') && $siteNode->getContext()->getWorkspace()->getName() !== 'live') {
				$output = '<div class="neos-rendering-exception"><div class="neos-rendering-exception-title">Failed to render element' . $output . '</div></div>';
			}
			$node = $currentContext['node'];
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
