<?php
namespace TYPO3\TYPO3CR\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which centralizes the logging of node operations
 *
 * @FLOW3\Aspect
 * @FLOW3\Scope("singleton")
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 * @FLOW3\Inject
	 */
	protected $systemLogger;

	/**
	 * Logs calls of remove()
	 *
	 * @FLOW3\AfterReturning("method(TYPO3\TYPO3CR\Domain\Model\Node->remove())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logRemove(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$node = $joinPoint->getProxy();
		$this->systemLogger->log(sprintf('Removed node "%s" from workspace "%s" (identifier: "%s")', $node->getPath(), $node->getWorkspace()->getName(), $node->getIdentifier()), LOG_DEBUG);
	}

}

?>