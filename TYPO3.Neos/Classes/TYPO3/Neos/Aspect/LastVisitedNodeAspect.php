<?php
namespace TYPO3\Neos\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * This aspect intercepts the Frontend node controller and writes the last
 * visited node's URI to the session.
 * This is for being able to redirect to the last visited node after login or logout.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class LastVisitedNodeAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * When there is no authenticated editor-like user, the last visited URI
	 * is stored on client side in order to avoid an unnecessary session initialization.
	 * If the authenticated editor is browsing through the sites, the fetched pages
	 * will go through the Frontend\NodeController's show action. We intercept this
	 * action and store the demanded URI in the session, considering it the last visited
	 * page's URI.
	 *
	 * @param \TYPO3\Flow\AOP\JoinPointInterface $joinPoint
	 * @Flow\AfterReturning("method(TYPO3\Neos\Controller\Frontend\NodeController->showAction())")
	 * @return void
	 */
	public function frontendShowNode(\TYPO3\Flow\AOP\JoinPointInterface $joinPoint) {
		if ($this->securityContext->isInitialized()) {
			$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
			if ($user !== NULL) {
				$frontendNodeController = $joinPoint->getProxy();
				$lastVisitedUri = $frontendNodeController->getControllerContext()->getRequest()->getParentRequest()->getUri();
				if ($lastVisitedUri !== NULL) {
					$this->session->putData('lastVisitedUri', (string)$lastVisitedUri);
				}
			}
		}
	}

}
?>