<?php
namespace TYPO3\TYPO3\Logging;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler;

/**
 * An aspect which centralizes the logging of TYPO3's routing functions.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @aspect
 * @scope singleton
 */
class RoutingLoggingAspect {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * Logs results of the FrontendNodeRoutePartHandler's matchValue() methods
	 *
	 * @afterreturning method(TYPO3\TYPO3\Routing\FrontendNodeRoutePartHandler->matchValue()) || method(TYPO3\TYPO3\Routing\RestRestServiceNodeRoutePartHandler->matchValue())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logMatchValue(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$path = $joinPoint->getMethodArgument('value');
		$resultCode = $joinPoint->getResult();

		switch (TRUE) {
			case $resultCode === FrontendNodeRoutePartHandler::MATCHRESULT_INVALIDPATH :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because the path was not valid.', LOG_INFO);
				break;
			case $resultCode === FrontendNodeRoutePartHandler::MATCHRESULT_NOWORKSPACE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because no workspace was found.', LOG_INFO);
				break;
			case $resultCode === FrontendNodeRoutePartHandler::MATCHRESULT_NOSITE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because no site was found.', LOG_INFO);
				break;
			case $resultCode === FrontendNodeRoutePartHandler::MATCHRESULT_NOSITENODE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match because no site node was found.', LOG_INFO);
				break;
			case $resultCode === FrontendNodeRoutePartHandler::MATCHRESULT_NOSUCHNODE :
				$this->systemLogger->log($joinPoint->getClassName() . ' did not match path "' . $path . '" because no such node was found.', LOG_INFO);
				break;
			case $resultCode === FrontendNodeRoutePartHandler::MATCHRESULT_FOUND :
				$contextNodePath = \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($joinPoint->getProxy(), 'value', TRUE);
				$this->systemLogger->log($joinPoint->getClassName() . ' matched node "' . $contextNodePath . '".', LOG_INFO);
				break;
		}
	}
}

?>
