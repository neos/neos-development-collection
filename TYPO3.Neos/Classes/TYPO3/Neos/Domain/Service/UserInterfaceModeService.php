<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Exception;
use TYPO3\Neos\Domain\Model\UserInterfaceMode;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;

/**
 * Service to build and find UserInterfaceMode objects
 *
 * @Flow\Scope("singleton")
 */
class UserInterfaceModeService
{
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
    public function findModeByCurrentUser()
    {
        if ($this->userService->getBackendUser() === null || !$this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess')) {
            return $this->findModeByName('live');
        }

        /** @var \TYPO3\Neos\Domain\Model\User $user */
        $editPreviewMode = $this->userService->getUserPreference('contentEditing.editPreviewMode');
        if ($editPreviewMode === null) {
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
    public function findDefaultMode()
    {
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
    public function findModeByName($modeName)
    {
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
