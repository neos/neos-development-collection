<?php
namespace TYPO3\Neos\Service;

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
class UserService
{
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
     * @return User
     */
    public function getBackendUser()
    {
        if ($this->securityContext->canBeInitialized() === true) {
            return $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
        }
        return null;
    }

    /**
     * Returns the Workspace of the currently logged in user or NULL if no matching workspace was found.
     * If no user is logged in this returns the live workspace
     *
     * @return Workspace
     */
    public function getCurrentWorkspace()
    {
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
    public function getCurrentWorkspaceName()
    {
        $account = $this->securityContext->getAccount();
        if ($account === null) {
            return 'live';
        }
        return 'user-' . preg_replace('/[^a-z0-9]/i', '', $account->getAccountIdentifier());
    }
}
