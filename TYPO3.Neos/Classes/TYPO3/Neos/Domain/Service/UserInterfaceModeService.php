<?php
namespace TYPO3\Neos\Domain\Service;

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
use TYPO3\Neos\Domain\Exception;
use TYPO3\Neos\Domain\Model\UserInterfaceMode;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;

/**
 * Service to build and find UserInterfaceMode objects
 *
 * @Flow\Scope("singleton")
 */
class UserInterfaceModeService {

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\UserService
	 */
	protected $userService;

	/**
	 * @Flow\InjectConfiguration(path="userInterface.editPreviewModes", package="TYPO3.Neos")
	 * @var array
	 */
	protected $editPreviewModes;

	/**
	 * @Flow\InjectConfiguration(path="userInterface.defaultEditPreviewMode", package="TYPO3.Neos")
	 * @var string
	 */
	protected $defaultEditPreviewMode;

	/**
	 * Get the current rendering mode (editPreviewMode).
	 * Will return a live mode when not in backend.
	 *
	 * @return UserInterfaceMode
	 */
	public function findModeByCurrentUser() {
		if ($this->userService->getBackendUser() === NULL || !$this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess')) {
			return $this->findModeByName('live');
		}

		/** @var \TYPO3\Neos\Domain\Model\User $user */
		$editPreviewMode = $this->userService->getUserPreference('contentEditing.editPreviewMode');
		if ($editPreviewMode === NULL) {
			$editPreviewMode = $this->defaultEditPreviewMode;
		}

		$mode = $this->findModeByName($editPreviewMode);

		return $mode;
	}

	/**
	 * Returns the default rendering mode.
	 *
	 * @return UserInterfaceMode
	 */
	public function findDefaultMode() {
		$mode = $this->findModeByName($this->defaultEditPreviewMode);

		return $mode;
	}

	/**
	 * Finds an rendering mode by name.
	 *
	 * @param string $modeName
	 * @return UserInterfaceMode
	 * @throws Exception
	 */
	public function findModeByName($modeName) {
		if (isset($this->editPreviewModes[$modeName])) {
			if ($this->editPreviewModes[$modeName] instanceof UserInterfaceMode) {
				$mode = $this->editPreviewModes[$modeName];
			} elseif (is_array($this->editPreviewModes[$modeName])) {
				$mode = UserInterfaceMode::createByConfiguration($modeName, $this->editPreviewModes[$modeName]);
				$this->editPreviewModes[$modeName] = $mode;
			} else {
				throw new Exception('The requested interface render mode "' . $modeName . '" is not configured correctly. Please make sure it is fully configured.', 1427716331);
			}
		} else {
			throw new Exception('The requested interface render mode "' . $modeName . '" is not configured. Please make sure it exists as key in the Settings path "TYPO3.Neos.Interface.editPreviewModes".', 1427715962);
		}

		return $mode;
	}
}