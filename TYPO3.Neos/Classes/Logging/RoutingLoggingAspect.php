<?php
namespace TYPO3\TYPO3\Logging;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which centralizes the logging of TYPO3's routing functions.
 *
 * @FLOW3\Aspect
 * @FLOW3\Scope("singleton")
 */
class RoutingLoggingAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * Logs successful results of the NodeService's getNodeByContextNodePath() method which is called by FrontendNodeRoutePartHandler::matchValue()
	 *
	 * @FLOW3\AfterReturning("method(TYPO3\TYPO3\Service\NodeService->getNodeByContextNodePath())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function logSuccessfulMatch(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$relativeContextNodePath = $joinPoint->getMethodArgument('relativeContextNodePath');
		$returnedNode = $joinPoint->getResult();
		$this->systemLogger->log(sprintf('%s matched node "%s" for path "%s"', $joinPoint->getClassName(), $returnedNode->getContextPath(), $relativeContextNodePath), LOG_INFO);
	}

	/**
	 * Logs exceptional results of the NodeService's getNodeByContextNodePath() method which is called by FrontendNodeRoutePartHandler::matchValue()
	 *
	 * @FLOW3\AfterThrowing("method(TYPO3\TYPO3\Service\NodeService->getNodeByContextNodePath())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function logFailedMatch(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$relativeContextNodePath = $joinPoint->getMethodArgument('relativeContextNodePath');
		$exception = $joinPoint->getException();
		if ($exception !== NULL) {
			$this->systemLogger->log(sprintf('%s failed to retrieve a node for path "%s" with message: %s', $joinPoint->getClassName(), $relativeContextNodePath, $exception->getMessage()), LOG_INFO);
		}
	}
}
?>