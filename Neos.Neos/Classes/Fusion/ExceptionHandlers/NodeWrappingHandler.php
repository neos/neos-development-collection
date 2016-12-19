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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Utility\Environment;
use Neos\Neos\Service\ContentElementWrappingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler;
use Neos\Fusion\Core\ExceptionHandlers\ContextDependentHandler;

/**
 * Provides a nicely formatted html error message
 * including all wrappers of an content element (i.e. menu allowing to
 * discard the broken element)
 */
class NodeWrappingHandler extends AbstractRenderingExceptionHandler
{
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
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        $handler = new ContextDependentHandler();
        $handler->setRuntime($this->runtime);
        $output = $handler->handleRenderingException($typoScriptPath, $exception);

        $currentContext = $this->getRuntime()->getCurrentContext();
        if (isset($currentContext['node'])) {
            /** @var NodeInterface $node */
            $node = $currentContext['node'];
            $applicationContext = $this->environment->getContext();
            if ($applicationContext->isProduction() && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess') && $node->getContext()->getWorkspaceName() !== 'live') {
                $output = '<div class="neos-rendering-exception"><div class="neos-rendering-exception-title">Failed to render element' . $output . '</div></div>';
            }

            return $this->contentElementWrappingService->wrapContentObject($node, $output, $typoScriptPath);
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
    protected function getMessage(\Exception $exception, $referenceCode)
    {
        if (isset($referenceCode)) {
            return sprintf('%s (%s)', $exception->getMessage(), $referenceCode);
        }
        return $exception->getMessage();
    }
}
