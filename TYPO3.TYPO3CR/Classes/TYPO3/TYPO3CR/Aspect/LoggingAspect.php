<?php
namespace TYPO3\TYPO3CR\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect which centralizes the logging of node operations
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class LoggingAspect {

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Logs calls of remove()
	 *
	 * @Flow\AfterReturning("method(TYPO3\TYPO3CR\Domain\Model\Node->remove())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function logRemove(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$node = $joinPoint->getProxy();
		$this->systemLogger->log(sprintf('Removed node "%s" from workspace "%s" (identifier: "%s")', $node->getPath(), $node->getWorkspace()->getName(), $node->getIdentifier()), LOG_DEBUG);
	}

}

?>