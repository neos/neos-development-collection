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
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Fluid\View\StandaloneView;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use TYPO3\Neos\Service\ContentElementWrappingService;

/**
 * A special exception handler that is used on the outer path to catch all unhandled exceptions and uses other exception
 * handlers depending on the login status.
 */
class PageHandler extends AbstractRenderingExceptionHandler {

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * @Flow\Inject
	 * @var ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * Handle an exception by displaying an error message inside the Neos backend, if logged in and not displaying the live workspace.
	 *
	 * @param array $typoScriptPath path causing the exception
	 * @param \Exception $exception exception to handle
	 * @param integer $referenceCode
	 * @return string
	 */
	protected function handle($typoScriptPath, \Exception $exception, $referenceCode) {
		$handler = new ContextDependentHandler();
		$handler->setRuntime($this->runtime);
		$output = $handler->handleRenderingException($typoScriptPath, $exception);
		$currentContext = $this->runtime->getCurrentContext();
		/** @var NodeInterface $documentNode */
		$documentNode = $currentContext['documentNode'];
		/** @var NodeInterface $node */
		$node = $currentContext['node'];

		$fluidView = $this->prepareFluidView();
		$isBackend = FALSE;
		/** @var NodeInterface $siteNode */
		$siteNode = $currentContext['site'];
		if ($this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess') && $siteNode->getContext()->getWorkspace()->getName() !== 'live') {
			$isBackend = TRUE;
			$fluidView->assign('metaData', $this->contentElementWrappingService->wrapContentObject($documentNode, $typoScriptPath, '<div id="neos-document-metadata"></div>'));
		}

		$fluidView->assignMultiple(array(
			'isBackend' => $isBackend,
			'message' => $output,
			'node' => $node
		));

		return $fluidView->render();
	}

	/**
	 * Prepare a Fluid view for rendering an error page with the Neos backend
	 *
	 * @return StandaloneView
	 */
	protected function prepareFluidView() {
		$fluidView = new StandaloneView();
		$fluidView->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Error/NeosBackendMessage.html');
		$fluidView->setLayoutRootPath('resource://TYPO3.Neos/Private/Layouts');
		// FIXME find a better way than using templates as partials
		$fluidView->setPartialRootPath('resource://TYPO3.Neos/Private/Templates/TypoScriptObjects');
		$fluidView->setFormat('html');
		$fluidView->setControllerContext($this->runtime->getControllerContext());
		return $fluidView;
	}

}
