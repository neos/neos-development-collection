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
 * @version $Id$
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
	 * Logs results of the PageRoutePartHandler's matchValue() methods
	 *
	 * @afterreturning method(F3\TYPO3\Routing\PageRoutePartHandler->matchValue())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logMatchValue(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
<<<<<<< .mine
		$result = $joinPoint->getResult();
		$path = $joinPoint->getMethodArgument('value');
		
     	switch (TRUE) {
			case $result === \F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSITE :
				$this->systemLogger->log('PageRoutePartHandler did not match path "' . $path . '" because no site was found.', LOG_WARNING);
=======
		$result = $joinPoint->getResult();
		$path = $joinPoint->getMethodArgument('value');
     	switch (TRUE) {
			case $result === \F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSITE :
				$this->systemLogger->log('PageRoutePartHandler did not match path "' . $path . '" because no site was found.', LOG_WARNING);
>>>>>>> .r4328
				break;
<<<<<<< .mine
			case $result === \F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSUCHNODE :
				$this->systemLogger->log('PageRoutePartHandler did not match path "' . $path . '" because no root node was found.', LOG_WARNING);
=======
			case $result === \F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSUCHNODE :
				$this->systemLogger->log('PageRoutePartHandler did not match path "' . $path . '" because no such node was found.', LOG_INFO);
>>>>>>> .r4328
				break;
			case $result === \F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_NOSUCHPAGE :
				$this->systemLogger->log('PageRoutePartHandler did not match path "' . $path . '" because no page was found at the site\'s root node.', LOG_WARNING);
				break;
			case $result === \F3\TYPO3\Routing\PageRoutePartHandler::MATCHRESULT_FOUND :
				$identityArray = $joinPoint->getProxy()->FLOW3_AOP_Proxy_getProperty('value');
				$uuid = $identityArray['__identity'];
				$this->systemLogger->log('PageRoutePartHandler matched page with UUID ' . $uuid . ' on path "' . $path . '".', LOG_DEBUG);
				break;
		}
	}
}

?>