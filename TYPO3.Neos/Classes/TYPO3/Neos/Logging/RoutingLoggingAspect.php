<?php
namespace TYPO3\Neos\Logging;

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

/**
 * An aspect which centralizes the logging of TYPO3 Neos routing functions.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class RoutingLoggingAspect
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Logs successful results of the NodeService's getNodeByContextNodePath() method which is called by FrontendNodeRoutePartHandler::matchValue()
     *
     * @Flow\AfterReturning("method(TYPO3\Neos\Service\NodeService->getNodeByContextNodePath())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function logSuccessfulMatch(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $relativeContextNodePath = $joinPoint->getMethodArgument('relativeContextNodePath');
        $returnedNode = $joinPoint->getResult();
        $this->systemLogger->log(sprintf('%s matched node "%s" for path "%s"', $joinPoint->getClassName(), $returnedNode->getContextPath(), $relativeContextNodePath), LOG_INFO);
    }

    /**
     * Logs exceptional results of the NodeService's getNodeByContextNodePath() method which is called by FrontendNodeRoutePartHandler::matchValue()
     *
     * @Flow\AfterThrowing("method(TYPO3\Neos\Service\NodeService->getNodeByContextNodePath())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function logFailedMatch(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $relativeContextNodePath = $joinPoint->getMethodArgument('relativeContextNodePath');
        $exception = $joinPoint->getException();
        if ($exception !== null) {
            $this->systemLogger->log(sprintf('%s failed to retrieve a node for path "%s" with message: %s', $joinPoint->getClassName(), $relativeContextNodePath, $exception->getMessage()), LOG_INFO);
        }
    }
}
