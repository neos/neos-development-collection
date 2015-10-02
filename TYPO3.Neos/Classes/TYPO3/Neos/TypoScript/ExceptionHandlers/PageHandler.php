<?php
namespace TYPO3\Neos\TypoScript\ExceptionHandlers;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use TYPO3\Neos\TypoScript\ExceptionHandlers\ContextDependentHandler;
use TYPO3\Neos\Service\ContentElementWrappingService;

/**
 * A special exception handler that is used on the outer path to catch all unhandled exceptions and uses other exception
 * handlers depending on the login status.
 */
class PageHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var AccessDecisionManagerInterface
     */
    protected $accessDecisionManager;

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
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        $handler = new ContextDependentHandler();
        $handler->setRuntime($this->runtime);
        $output = $handler->handleRenderingException($typoScriptPath, $exception);
        $currentContext = $this->runtime->getCurrentContext();
        /** @var NodeInterface $documentNode */
        $documentNode = isset($currentContext['documentNode']) ? $currentContext['documentNode'] : null;

        /** @var NodeInterface $node */
        $node = isset($currentContext['node']) ? $currentContext['node'] : null;

        $fluidView = $this->prepareFluidView();
        $isBackend = false;
        /** @var NodeInterface $siteNode */
        $siteNode = isset($currentContext['site']) ? $currentContext['site'] : null;

        if ($documentNode === null) {
            // Actually we cannot be sure that $node is a document. But for fallback purposes this should be safe.
            $documentNode = $siteNode ? $siteNode : $node;
        }

        if ($documentNode !== null && $documentNode->getContext()->getWorkspace()->getName() !== 'live' && $this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess')) {
            $isBackend = true;
            $fluidView->assign('metaData', $this->contentElementWrappingService->wrapContentObject($documentNode, $typoScriptPath, '<div id="neos-document-metadata"></div>', true));
        }

        $fluidView->assignMultiple(array(
            'isBackend' => $isBackend,
            'message' => $output,
            'node' => $node
        ));

        return $fluidView->render();
    }

    /**
     * Prepare fluid view for rendering error page with neos backend
     *
     * @return \TYPO3\Fluid\View\StandaloneView
     */
    protected function prepareFluidView()
    {
        $fluidView = new \TYPO3\Fluid\View\StandaloneView();
        $fluidView->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Error/NeosBackendMessage.html');
        $fluidView->setLayoutRootPath('resource://TYPO3.Neos/Private/Layouts');
        // FIXME find a better way than using templates as partials
        $fluidView->setPartialRootPath('resource://TYPO3.Neos/Private/Templates/TypoScriptObjects');
        $fluidView->setFormat('html');
        return $fluidView;
    }
}
