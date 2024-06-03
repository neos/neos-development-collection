<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\ExceptionHandlers;

use GuzzleHttp\Psr7\Message;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Utility\Environment;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use Neos\Fusion\Core\ExceptionHandlers\HtmlMessageHandler;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Service\ContentElementWrappingService;
use Psr\Http\Message\ResponseInterface;

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
     * @var ContentElementWrappingService
     */
    protected $contentElementWrappingService;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Handle an exception by displaying an error message inside the Neos backend,
     * if logged in and not displaying the live workspace.
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param string|null $referenceCode
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode)
    {
        $handler = new HtmlMessageHandler($this->environment->getContext()->isDevelopment());
        $handler->setRuntime($this->runtime);
        $output = $handler->handleRenderingException($fusionPath, $exception);
        $currentContext = $this->runtime->getCurrentContext();
        /** @var ?Node $documentNode */
        $documentNode = $currentContext['documentNode'] ?? null;

        /** @var ?Node $node */
        $node = $currentContext['node'] ?? null;

        $fluidView = $this->prepareFluidView();
        $isBackend = false;
        /** @var ?Node $siteNode */
        $siteNode = $currentContext['site'] ?? null;

        if ($documentNode === null) {
            // Actually we cannot be sure that $node is a document. But for fallback purposes this should be safe.
            $documentNode = $siteNode ?: $node;
        }

        if (!is_null($documentNode)) {
            $renderingMode = $this->runtime->fusionGlobals->get('renderingMode');
            assert($renderingMode instanceof RenderingMode);
            if (
                $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')
                && $renderingMode->isEdit
            ) {
                $isBackend = true;
            }
        }

        $fluidView->assignMultiple([
            'isBackend' => $isBackend,
            'message' => $output,
            'node' => $node
        ]);

        return $this->wrapHttpResponse($exception, $fluidView->render()->getContents());
    }

    /**
     * Renders an actual HTTP response including the correct status and cache control header.
     */
    protected function wrapHttpResponse(\Exception $exception, string $bodyContent): string
    {
        /** @var ResponseInterface $response */
        $response = new \GuzzleHttp\Psr7\Response(
            $exception instanceof FlowException ? $exception->getStatusCode() : 500,
            ['Cache-Control' => 'no-store'],
            $bodyContent
        );

        return Message::toString($response);
    }

    /**
     * Prepare a Fluid view for rendering an error page with the Neos backend
     *
     * @return StandaloneView
     */
    protected function prepareFluidView()
    {
        $fluidView = new StandaloneView();
        $fluidView->setControllerContext(
            new ControllerContext(
                $this->runtime->getControllerContext()->getRequest(),
                $this->runtime->getControllerContext()->getResponse(),
                new Arguments(),
                $this->runtime->getControllerContext()->getUriBuilder()
            )
        );
        $fluidView->setFormat('html');
        $fluidView->setTemplatePathAndFilename('resource://Neos.Neos/Private/Templates/Error/NeosBackendMessage.html');
        $fluidView->setLayoutRootPath('resource://Neos.Neos/Private/Layouts/');
        // FIXME find a better way than using templates as partials
        $fluidView->setPartialRootPath('resource://Neos.Neos/Private/Templates/FusionObjects/');
        return $fluidView;
    }
}
