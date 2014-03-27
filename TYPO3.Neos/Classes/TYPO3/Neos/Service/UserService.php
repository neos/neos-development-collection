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
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * The user service provides general context information about the currently
 * authenticated backend user.
 *
 * @Flow\Scope("singleton")
 */
class UserService {

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject(setting="userInterface.locale")
	 * @var string
	 */
	protected $locale;

	/**
	 * @return User
	 */
	public function getBackendUser() {
		if ($this->securityContext->isInitialized() === TRUE) {
			return $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		}
		return NULL;
	}

	/**
	 * Returns the Workspace of the currently logged in user or NULL if no matching workspace was found.
	 * If no user is logged in this returns the live workspace
	 *
	 * @return Workspace
	 */
	public function getCurrentWorkspace() {
		return $this->workspaceRepository->findOneByName($this->getCurrentWorkspaceName());
	}

	/**
	 * Returns the Workspace name of the currently logged in user (even if that might not exist at that time)
	 * If no user is logged in this returns "live"
	 *
	 * Note: This currently always constructs the workspace name from the logged in users account identifier (username)
	 * In the future a user can have access to more than one workspace
	 *
	 * @return string
	 */
	public function getCurrentWorkspaceName() {
		$account = $this->securityContext->getAccount();
		if ($account === NULL) {
			return 'live';
		}
		return 'user-' . preg_replace('/[^a-z0-9]/i', '', $account->getAccountIdentifier());
	}

	/**
	 * Returns the preference of a user
	 *
	 * @param string $preference
	 * @return string
	 */
	public function getUserPreference($preference) {
		$user = $this->getBackendUser();
		if ($user && $user->getPreferences()) {
			return $user->getPreferences()->get($preference) ? $user->getPreferences()->get($preference) : NULL;
		}
	}

	/**
	 * Returns the user preferred locale if set. Else will fallback to the original settings
	 *
	 * @return string
	 */
	public function getUserLocale() {
		$userLocale = $this->getUserPreference('interfaceLocale');
		return $userLocale ? $userLocale : $this->locale;
	}
}
