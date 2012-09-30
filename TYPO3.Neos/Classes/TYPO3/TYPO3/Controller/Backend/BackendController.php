<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The TYPO3 Backend controller
 *
 * @Flow\Scope("singleton")
 */
class BackendController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Default action of the backend controller.
	 *
	 * @return void
	 * @Flow\SkipCsrfProtection
	 */
	public function indexAction() {
		$workspaceName = $this->securityContext->getParty()->getPreferences()->get('context.workspace');

			// Hack: Create the workspace if it does not exist yet.
		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext($workspaceName);
		$contentContext->getWorkspace();

		if (isset($_COOKIE['TYPO3_lastVisitedUri'])) {
			$redirectUri = $_COOKIE['TYPO3_lastVisitedUri'];
			$appendHtml = !strpos($redirectUri, '.html') ? FALSE : TRUE;
			if (!strpos($redirectUri, '@')) {
				$redirectUri = str_replace('.html', '', $redirectUri);
			} else {
				$redirectUri = substr($redirectUri, 0, strpos($redirectUri, '@'));
			}
			$redirectUri .= '@' . $workspaceName . ($appendHtml === TRUE ? '.html' : '');
			$this->redirectToUri($redirectUri);
		} else {
			$this->redirectToUri('/@' . $workspaceName . '.html');
		}
	}
}
?>