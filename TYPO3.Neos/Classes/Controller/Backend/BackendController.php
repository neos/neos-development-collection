<?php
namespace TYPO3\TYPO3\Controller\Backend;

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

/**
 * The TYPO3 Backend controller
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class BackendController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Default action of the backend controller.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @skipCsrfProtection
	 */
	public function indexAction() {
		$workspaceName = $this->securityContext->getParty()->getPreferences()->get('context.workspace');
		if (isset($_COOKIE['TYPO3_lastVisitedUri'])) {
			$this->redirectToUri(str_replace('.html', '@' . $workspaceName . '.html', $_COOKIE['TYPO3_lastVisitedUri']));
		} else {
			$this->redirectToUri('/@' . $workspaceName . '.html');
		}
	}
}
?>