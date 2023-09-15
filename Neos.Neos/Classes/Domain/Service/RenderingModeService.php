<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;

/**
 * Service to build and find RenderingMode objects
 *
 * @Flow\Scope("singleton")
 */
class RenderingModeService
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
     * @phpstan-var array<string,mixed>
     */
    protected $editPreviewModes;

    /**
     * @Flow\InjectConfiguration(path="userInterface.defaultEditPreviewMode", package="Neos.Neos")
     * @var string
     */
    protected $defaultEditPreviewMode;

    /**
     * Get the current rendering mode.
     * Will return a live mode when not in backend.
     */
    public function findByCurrentUser(): RenderingMode
    {
        if (
            $this->userService->getBackendUser() === null
            || !$this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')
        ) {
            return RenderingMode::createFrontend();
        }

        $modeName = $this->userService->getUserPreference('contentEditing.editPreviewMode');
        if ($modeName === null) {
            $modeName = $this->defaultEditPreviewMode;
        }

        return $this->findByName($modeName);
    }

    /**
     * Returns the default rendering mode.
     */
    public function findDefault(): RenderingMode
    {
        return $this->findByName($this->defaultEditPreviewMode);
    }

    /**
     * Finds an rendering mode by name.
     */
    public function findByName(string $modeName): RenderingMode
    {
        if ($modeName === RenderingMode::FRONTEND) {
            return RenderingMode::createFrontend();
        }
        if (isset($this->editPreviewModes[$modeName])) {
            return RenderingMode::createFromConfiguration($modeName, $this->editPreviewModes[$modeName]);
        }
        throw new Exception(
            'The requested rendering mode "' . $modeName . '" is not configured.'
                . ' Please make sure it exists as key in the Settings path "Neos.Neos.Interface.editPreviewModes".',
            1427715962
        );
    }
}
