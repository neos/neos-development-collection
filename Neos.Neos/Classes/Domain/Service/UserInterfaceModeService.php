<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Model\UserInterfaceMode;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;

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
     * @var \Neos\Neos\Service\UserService
     */
    protected $userService;

    /**
     * @Flow\InjectConfiguration(path="userInterface.editPreviewModes", package="Neos.Neos")
     * @var array
     */
    protected $editPreviewModes;

    /**
     * @Flow\InjectConfiguration(path="userInterface.defaultEditPreviewMode", package="Neos.Neos")
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
        if ($this->userService->getBackendUser() === null
            || !$this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')
        ) {
            return $this->findModeByName('live');
        }

        /** @var \Neos\Neos\Domain\Model\User $user */
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
                throw new Exception(
                    'The requested interface render mode "' . $modeName . '" is not configured correctly.'
                        . ' Please make sure it is fully configured.',
                    1427716331
                );
            }
        } else {
            throw new Exception(
                'The requested interface render mode "' . $modeName . '" is not configured.'
                    . ' Please make sure it exists as key in the Settings path "Neos.Neos.Interface.editPreviewModes".',
                1427715962
            );
        }

        return $mode;
    }
}
