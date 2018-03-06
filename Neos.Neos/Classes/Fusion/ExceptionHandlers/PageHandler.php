<?php
namespace Neos\Neos\Fusion\ExceptionHandlers;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use Neos\Neos\Service\ContentElementWrappingServiceInterface;
use Neos\Fusion\Core\ExceptionHandlers\ContextDependentHandler;

/**
 * A special exception handler that is used on the outer path to catch all unhandled exceptions and uses other exception
 * handlers depending on the login status.
 */
class PageHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ContentElementWrappingServiceInterface
     */
    protected $contentElementWrappingService;

    /**
     * Handle an exception by displaying an error message inside the Neos backend, if logged in and not displaying the live workspace.
     *
     * @param array $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     * @throws \Neos\Flow\Mvc\Exception\StopActionException|\Neos\Flow\Security\Exception
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode)
    {
        $handler = new ContextDependentHandler();
        $handler->setRuntime($this->runtime);
        $output = $handler->handleRenderingException($fusionPath, $exception);
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

        if ($documentNode !== null && $this->getCurrentWorkspaceName() && !$this->getCurrentWorkspaceName()->isLive() && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $isBackend = true;
            $fluidView->assign('metaData', $this->contentElementWrappingService->wrapCurrentDocumentMetadata($documentNode, '<div id="neos-document-metadata"></div>', $fusionPath));
        }

        $fluidView->assignMultiple(array(
            'isBackend' => $isBackend,
            'message' => $output,
            'node' => $node
        ));

        return $fluidView->render();
    }

    /**
     * @return WorkspaceName|null
     */
    protected function getCurrentWorkspaceName(): ?WorkspaceName
    {
        return $this->runtime->getCurrentContext()['workspaceName'] ?? null;
    }

    /**
     * Prepare a Fluid view for rendering an error page with the Neos backend
     *
     * @return StandaloneView
     */
    protected function prepareFluidView()
    {
        $fluidView = new StandaloneView();
        $fluidView->setControllerContext($this->runtime->getControllerContext());
        $fluidView->setFormat('html');
        $fluidView->setTemplatePathAndFilename('resource://Neos.Neos/Private/Templates/Error/NeosBackendMessage.html');
        $fluidView->setLayoutRootPath('resource://Neos.Neos/Private/Layouts/');
        // FIXME find a better way than using templates as partials
        $fluidView->setPartialRootPath('resource://Neos.Neos/Private/Templates/FusionObjects/');

        return $fluidView;
    }
}
