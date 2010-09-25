<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Logging;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An aspect which centralizes the logging of TYPO3's routing functions.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class RoutingLoggingAspect {

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Logs results of the NodeRoutePartHandler's matchValue() methods
	 *
	 * @afterreturning method(F3\TYPO3\Routing\NodeRoutePartHandler->matchValue()) || method(F3\TYPO3\Routing\NodeServiceRoutePartHandler->matchValue())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logMatchValue(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$path = $joinPoint->getMethodArgument('value');
		$resultCode = $joinPoint->getResult();

     	switch (TRUE) {
			case $resultCode === \F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_INVALIDPATH :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because the path was not valid.', LOG_INFO);
				break;
			case $resultCode === \F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_NOWORKSPACE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because no workspace was found.', LOG_WARNING);
				break;
			case $resultCode === \F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_NOSITE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because no site was found.', LOG_WARNING);
				break;
			case $resultCode === \F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_NOSITENODE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match because no site node was found.', LOG_WARNING);
				break;
			case $resultCode === \F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_NOSUCHNODE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because no such node was found.', LOG_WARNING);
				break;
			case $resultCode === \F3\TYPO3\Routing\NodeRoutePartHandler::MATCHRESULT_FOUND :
				$node = $joinPoint->getProxy()->FLOW3_AOP_Proxy_getProperty('value');
				$this->systemLogger->log($joinPoint->getClassName() . ' matched node "' . $node->getPath() . '" of type ' . $node->getContentType() . '.', LOG_INFO);
				break;
		}
	}
}

?>