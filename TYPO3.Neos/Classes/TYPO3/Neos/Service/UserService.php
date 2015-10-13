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
     * @Flow\InjectConfiguration("userInterface.defaultLanguage")
     * @var string
     */
    protected $defaultLanguageIdentifier;

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
    public function getUserWorkspace()
    {
        return $this->workspaceRepository->findOneByName($this->getUserWorkspaceName());
    }

    /**
     * Returns the name of the currently logged in user's personal workspace (even if that might not exist at that time).
     * If no user is logged in this method returns "live".
     *
     * @return string
     */
    public function getUserWorkspaceName()
    {
        $account = $this->securityContext->getAccount();
        if ($account === null) {
            return 'live';
        }
        return 'user-' . preg_replace('/[^a-z0-9]/i', '', $account->getAccountIdentifier());
    }

    /**
     * Returns the preference of a user
     *
     * @param string $preference
     * @return mixed
     */
    public function getUserPreference($preference)
    {
        $user = $this->getBackendUser();
        if ($user && $user->getPreferences()) {
            return $user->getPreferences()->get($preference) ?: null;
        }
    }

    /**
     * Returns the interface language the user selected. Will fall back to the default language defined in settings
     *
     * @return string
     */
    public function getInterfaceLanguage()
    {
        return $this->getUserPreference('interfaceLanguage') ?: $this->defaultLanguageIdentifier;
    }
}
