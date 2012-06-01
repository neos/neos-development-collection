<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TYPO3 Backend controller
 *
 * @FLOW3\Scope("singleton")
 */
class BackendController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Default action of the backend controller.
	 *
	 * @return string
	 */
	public function indexAction() {
		$workspaceName = $this->securityContext->getParty()->getPreferences()->get('context.workspace');

			// Hack: Create the workspace if it does not exist yet.
		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext($workspaceName);
		$contentContext->getWorkspace();

		if (isset($_COOKIE['TYPO3_lastVisitedUri'])) {
			$redirectUri = $_COOKIE['TYPO3_lastVisitedUri'];
			if (!strpos($redirectUri, '@') && strpos($redirectUri, '.html') !== FALSE) {
				$redirectUri = str_replace('.html', '@' . $workspaceName . '.html', $redirectUri);
			} elseif (!strpos($redirectUri, '@')) {
				$redirectUri .= '@' . $workspaceName;
			}
			$this->redirectToUri($redirectUri);
		} else {
			$this->redirectToUri('/@' . $workspaceName . '.html');
		}
	}
}
?>