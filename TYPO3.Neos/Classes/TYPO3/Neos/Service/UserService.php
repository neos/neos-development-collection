<?php
namespace TYPO3\Neos\Service;

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
 * The user service provides general context information about the currently
 * authenticated backend user.
 *
 * @Flow\Scope("singleton")
 */
class UserService {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @return \TYPO3\Neos\Domain\Model\User
	 */
	public function getBackendUser() {
		if ($this->securityContext->isInitialized() === TRUE) {
			return $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		}
		return NULL;
	}

	/**
	 * @return string
	 */
	public function getCurrentWorkspace() {
		$user = $this->getBackendUser();

		if ($user === NULL) {
			return 'live';
		}

		return $user->getPreferences()->get('context.workspace');
	}
}
?>