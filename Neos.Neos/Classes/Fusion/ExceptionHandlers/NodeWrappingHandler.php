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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Utility\Environment;
use Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use Neos\Fusion\Core\ExceptionHandlers\ContextDependentHandler;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Service\ContentElementWrappingService;

/**
 * Provides a nicely formatted html error message
 * including all wrappers of an content element (i.e. menu allowing to
 * discard the broken element)
 */
class NodeWrappingHandler extends AbstractRenderingExceptionHandler
{
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
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * renders the exception to nice html content element to display, edit, remove, ...
     *
     * @param string $fusionPath - path causing the exception
     * @param \Exception $exception - exception to handle
     * @param string|null $referenceCode - might be unset
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode): string
    {
        $handler = new ContextDependentHandler();
        $handler->setRuntime($this->runtime);
        $output = (string)$handler->handleRenderingException($fusionPath, $exception);

        $currentContext = $this->getRuntime()->getCurrentContext();
        if (isset($currentContext['node'])) {
            /** @var Node $node */
            $node = $currentContext['node'];

            $renderingMode = $this->runtime->fusionGlobals->get('renderingMode');
            assert($renderingMode instanceof RenderingMode);
            $applicationContext = $this->environment->getContext();

            if (!$renderingMode->isEdit) {
                return $output;
            }

            if (!$this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
                return $output;
            }

            if ($applicationContext->isProduction()) {
                $output = '<div class="neos-rendering-exception">
    <div class="neos-rendering-exception-title">Failed to render element' . $output . '</div>
</div>';
            }

            return $this->contentElementWrappingService->wrapContentObject($node, $output, $fusionPath) ?: '';
        }

        return $output;
    }

    /**
     * appends the given reference code to the exception's message
     * unless it is unset
     */
    protected function getMessage(\Exception $exception, int|string|null $referenceCode = null): string
    {
        if (isset($referenceCode)) {
            return sprintf('%s (%s)', $exception->getMessage(), $referenceCode);
        }
        return $exception->getMessage();
    }
}
