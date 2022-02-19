<?php

declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\WorkspaceModule;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Neos\Domain\Service\UserService;

/**
 * A service for managing users
 *
 * @Flow\Scope("singleton")
 * @api
 */
class WorkspaceUserService
{

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * Checks if the current user may publish to the given workspace according to one the roles of the user's accounts
     *
     * In future versions, this logic may be implemented in Neos in a more generic way (for example, by means of an
     * ACL object), but for now, this method exists in order to at least centralize and encapsulate the required logic.
     *
     * @param Workspace $workspace The workspace
     * @return boolean
     */
    public function currentUserCanPublishToWorkspace(Workspace $workspace)
    {
        if ($workspace->getWorkspaceName()->jsonSerialize() === 'live') {
            return $this->securityContext->hasRole('Neos.Neos:LivePublisher');
        }

        $ownerIdentifier = $this->persistenceManager->getIdentifierByObject($this->userService->getCurrentUser());
        if ($workspace->getWorkspaceOwner() === $ownerIdentifier || $workspace->getWorkspaceOwner() === null) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the current user may manage the given workspace according to one the roles of the user's accounts
     *
     * In future versions, this logic may be implemented in Neos in a more generic way (for example, by means of an
     * ACL object), but for now, this method exists in order to at least centralize and encapsulate the required logic.
     *
     * @param Workspace $workspace The workspace
     * @return boolean
     */
    public function currentUserCanManageWorkspace(Workspace $workspace)
    {
        if ($workspace->isPersonalWorkspace()) {
            return false;
        }

        if ($workspace->isInternalWorkspace()) {
            return $this->privilegeManager->isPrivilegeTargetGranted(
                'Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces'
            );
        }

        if ($workspace->isPrivateWorkspace() && $workspace->getOwner() === $this->getCurrentUser()) {
            return $this->privilegeManager->isPrivilegeTargetGranted(
                'Neos.Neos:Backend.Module.Management.Workspaces.ManageOwnWorkspaces'
            );
        }

        if ($workspace->isPrivateWorkspace() && $workspace->getOwner() !== $this->getCurrentUser()) {
            return $this->privilegeManager->isPrivilegeTargetGranted(
                'Neos.Neos:Backend.Module.Management.Workspaces.ManageAllPrivateWorkspaces'
            );
        }

        return false;
    }

    /**
     * Checks if the current user may transfer ownership of the given workspace
     *
     * In future versions, this logic may be implemented in Neos in a more generic way (for example, by means of an
     * ACL object), but for now, this method exists in order to at least centralize and encapsulate the required logic.
     *
     * @param \Neos\ContentRepository\Domain\Model\Workspace $workspace The workspace
     * @return boolean
     */
    public function currentUserCanTransferOwnershipOfWorkspace(Workspace $workspace)
    {
        if ($workspace->isPersonalWorkspace()) {
            return false;
        }

        // The privilege to manage shared workspaces is needed, because regular editors should not change ownerships
        // of their internal workspaces, even if it was technically possible, because they wouldn't be able to change
        // ownership back to themselves.
        return $this->privilegeManager->isPrivilegeTargetGranted(
            'Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces'
        );
    }
}
