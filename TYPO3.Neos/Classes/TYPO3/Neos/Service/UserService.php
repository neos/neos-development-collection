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
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * The user service provides general context information about the currently
 * authenticated backend user.
 *
 * The methods getters of this class are accessible via the "context.userInformation" variable in security policies
 * and thus are implicitly considered to be part of the public API. This UserService should be replaced by
 * \TYPO3\Neos\Domain\Service\UserService in the long run.
 *
 * @Flow\Scope("singleton")
 * @api
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
     * Returns the current backend user
     *
     * @return User
     * @api
     */
    public function getBackendUser()
    {
        if ($this->securityContext->canBeInitialized() === true) {
            return $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
        }
        return null;
    }

    /**
     * Returns the current user's personal workspace or null if no user is logged in
     *
     * @return Workspace
     * @api
     */
    public function getPersonalWorkspace()
    {
        return $this->workspaceRepository->findOneByName($this->getPersonalWorkspaceName());
    }

    /**
     * Returns the name of the currently logged in user's personal workspace (even if that might not exist at that time).
     * If no user is logged in this method returns null.
     *
     * @return string
     * @api
     */
    public function getPersonalWorkspaceName()
    {
        $account = $this->securityContext->getAccount();
        if ($account instanceof Account) {
            return 'user-' . preg_replace('/[^a-z0-9]/i', '', $account->getAccountIdentifier());
        }
    }

    /**
     * Returns the current user's personal workspace or null if no user is logged in.
     * Deprecated, use getPersonalWorkspace() instead.
     *
     * @return Workspace
     * @api
     * @deprecated
     */
    public function getUserWorkspace()
    {
        return $this->getPersonalWorkspace();
    }

    /**
     * Returns the name of the currently logged in user's personal workspace (even if that might not exist at that time).
     * If no user is logged in this method returns null.
     * Deprecated, use getPersonalWorkspaceName() instead.
     *
     * @return string
     * @api
     * @deprecated
     */
    public function getUserWorkspaceName()
    {
        return $this->getPersonalWorkspaceName();
    }

    /**
     * Returns the stored preferences of a user
     *
     * @param string $preference
     * @return mixed
     * @api
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
     * @api
     */
    public function getInterfaceLanguage()
    {
        return $this->getUserPreference('interfaceLanguage') ?: $this->defaultLanguageIdentifier;
    }
}
