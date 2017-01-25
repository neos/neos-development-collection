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
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Utility\Now;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\UserRepository;
use Neos\Neos\Service\PublishingService;
use Neos\Party\Domain\Model\PersonName;
use Neos\Party\Domain\Repository\PartyRepository;
use Neos\Party\Domain\Service\PartyService;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\Neos\Utility\User as UserUtility;

/**
 * A service for managing users
 *
 * @Flow\Scope("singleton")
 * @api
 */
class UserService
{

    /**
     * Might be configurable in the future, for now centralising this as a "constant"
     *
     * @var string
     */
    protected $defaultAuthenticationProviderName = 'Neos.Neos:Backend';

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var PublishingService
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var PartyRepository
     */
    protected $partyRepository;

    /**
     * @Flow\Inject
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @Flow\Inject
     * @var PartyService
     */
    protected $partyService;

    /**
     * @Flow\Inject
     * @var AccountFactory
     */
    protected $accountFactory;

    /**
     * @Flow\Inject
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var Now
     */
    protected $now;

    /**
     * @var array
     */
    protected $runtimeUserCache = [];

    /**
     * Retrieves a list of all existing users
     *
     * @return array<User> The users
     * @api
     */
    public function getUsers()
    {
        return $this->userRepository->findAll();
    }

    /**
     * Retrieves an existing user by the given username
     *
     * @param string $username The username
     * @param string $authenticationProviderName Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return User The user, or null if the user does not exist
     * @throws Exception
     * @api
     */
    public function getUser($username, $authenticationProviderName = null)
    {
        if ($authenticationProviderName !== null && isset($this->runtimeUserCache['a_' . $authenticationProviderName][$username])) {
            return $this->runtimeUserCache['a_' . $authenticationProviderName][$username];
        } elseif (isset($this->runtimeUserCache['u_' . $username])) {
            return $this->runtimeUserCache['u_' . $username];
        }
        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProviderName ?: $this->defaultAuthenticationProviderName);
        if (!$account instanceof Account) {
            return null;
        }
        $user = $this->partyService->getAssignedPartyOfAccount($account);
        if (!$user instanceof User) {
            throw new Exception(sprintf('Unexpected user type "%s". An account with the identifier "%s" exists, but the corresponding party is not a Neos User.', get_class($user), $username), 1422270948);
        }
        if ($authenticationProviderName !== null) {
            if (!isset($this->runtimeUserCache['a_' . $authenticationProviderName])) {
                $this->runtimeUserCache['a_' . $authenticationProviderName] = [];
            }
            $this->runtimeUserCache['a_' . $authenticationProviderName][$username] = $user;
        } else {
            $this->runtimeUserCache['u_' . $username] = $user;
        }
        return $user;
    }

    /**
     * Returns the username of the given user
     *
     * Technically, this method will look for the user's backend account (or, if authenticationProviderName is specified,
     * for the account matching the given authentication provider) and return the account's identifier.
     *
     * @param User $user
     * @param string $authenticationProviderName
     * @return string The username or null if the given user does not have a backend account
     */
    public function getUsername(User $user, $authenticationProviderName = null)
    {
        $authenticationProviderName = $authenticationProviderName ?: $this->defaultAuthenticationProviderName;
        foreach ($user->getAccounts() as $account) {
            /** @var Account $account */
            if ($account->getAuthenticationProviderName() === $authenticationProviderName) {
                return $account->getAccountIdentifier();
            }
        }
        return null;
    }

    /**
     * Returns the currently logged in user, if any
     *
     * @return User The currently logged in user, or null
     * @api
     */
    public function getCurrentUser()
    {
        if ($this->securityContext->canBeInitialized() === true) {
            $account = $this->securityContext->getAccount();
            if ($account !== null) {
                return $this->getUser($account->getAccountIdentifier());
            }
        }

        return null;
    }

    /**
     * Creates a user based on the given information
     *
     * The created user and account are automatically added to their respective repositories and thus be persisted.
     *
     * @param string $username The username of the user to be created.
     * @param string $password Password of the user to be created
     * @param string $firstName First name of the user to be created
     * @param string $lastName Last name of the user to be created
     * @param array $roleIdentifiers A list of role identifiers to assign
     * @param string $authenticationProviderName Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return User The created user instance
     * @api
     */
    public function createUser($username, $password, $firstName, $lastName, array $roleIdentifiers = null, $authenticationProviderName = null)
    {
        $user = new User();
        $name = new PersonName('', $firstName, '', $lastName, '', $username);
        $user->setName($name);

        return $this->addUser($username, $password, $user, $roleIdentifiers, $authenticationProviderName);
    }

    /**
     * Adds a user whose User object has been created elsewhere
     *
     * This method basically "creates" a user like createUser() would, except that it does not create the User
     * object itself. If you need to create the User object elsewhere, for example in your ActionController, make sure
     * to call this method for registering the new user instead of adding it to the PartyRepository manually.
     *
     * This method also creates a new user workspace for the given user if no such workspace exist.
     *
     * @param string $username The username of the user to be created.
     * @param string $password Password of the user to be created
     * @param User $user The pre-built user object to start with
     * @param array $roleIdentifiers A list of role identifiers to assign
     * @param string $authenticationProviderName Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return User The same user object
     * @api
     */
    public function addUser($username, $password, User $user, array $roleIdentifiers = null, $authenticationProviderName = null)
    {
        if ($roleIdentifiers === null) {
            $roleIdentifiers = array('Neos.Neos:Editor');
        }
        $roleIdentifiers = $this->normalizeRoleIdentifiers($roleIdentifiers);
        $account = $this->accountFactory->createAccountWithPassword($username, $password, $roleIdentifiers, $authenticationProviderName ?: $this->defaultAuthenticationProviderName);
        $this->partyService->assignAccountToParty($account, $user);

        $this->partyRepository->add($user);
        $this->accountRepository->add($account);

        $this->createPersonalWorkspace($user, $account);

        $this->emitUserCreated($user);

        return $user;
    }

    /**
     * Signals that a new user, including a new account has been created.
     *
     * @param User $user The created user
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitUserCreated(User $user)
    {
    }

    /**
     * Deletes the specified user and all remaining content in his personal workspaces
     *
     * @param User $user The user to delete
     * @return void
     * @throws Exception
     * @api
     */
    public function deleteUser(User $user)
    {
        $backendUserRole = $this->policyService->getRole('Neos.Neos:AbstractEditor');
        foreach ($user->getAccounts() as $account) {
            /** @var Account $account */
            if ($account->hasRole($backendUserRole)) {
                $this->deletePersonalWorkspace($account->getAccountIdentifier());
            }
            $this->accountRepository->remove($account);
        }

        $this->removeOwnerFromUsersWorkspaces($user);

        $this->partyRepository->remove($user);
        $this->emitUserDeleted($user);
    }

    /**
     * Signals that the given user has been deleted.
     *
     * @param User $user The created user
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitUserDeleted(User $user)
    {
    }

    /**
     * Sets a new password for the given user
     *
     * This method will iterate over all accounts owned by the given user and, if the account uses a UsernamePasswordToken,
     * sets a new password accordingly.
     *
     * @param User $user The user to set the password for
     * @param string $password A new password
     * @return void
     * @api
     */
    public function setUserPassword(User $user, $password)
    {
        $tokens = $this->authenticationManager->getTokens();
        $indexedTokens = array();
        foreach ($tokens as $token) {
            /** @var TokenInterface $token */
            $indexedTokens[$token->getAuthenticationProviderName()] = $token;
        }

        foreach ($user->getAccounts() as $account) {
            /** @var Account $account */
            $authenticationProviderName = $account->getAuthenticationProviderName();
            if (isset($indexedTokens[$authenticationProviderName]) && $indexedTokens[$authenticationProviderName] instanceof UsernamePassword) {
                $account->setCredentialsSource($this->hashService->hashPassword($password));
                $this->accountRepository->update($account);
            }
        }
    }

    /**
     * Updates the given user in the respective repository and potentially executes further actions depending on what
     * has been changed.
     *
     * Note: changes to the user's account will not be committed for persistence. Please use addRoleToAccount(), removeRoleFromAccount(),
     * setRolesForAccount() and setUserPassword() for changing account properties.
     *
     * @param User $user The modified user
     * @return void
     * @api
     */
    public function updateUser(User $user)
    {
        $this->partyRepository->update($user);
        $this->emitUserUpdated($user);
    }

    /**
     * Adds the specified role to all accounts of the given user and potentially carries out further actions which are needed to
     * properly reflect these changes.
     *
     * @param User $user The user to add roles to
     * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the Neos.Neos namespace
     * @return integer How often this role has been added to accounts owned by the user
     * @api
     */
    public function addRoleToUser(User $user, $roleIdentifier)
    {
        $counter = 0;
        foreach ($user->getAccounts() as $account) {
            $counter += $this->addRoleToAccount($account, $roleIdentifier);
        }

        return $counter;
    }

    /**
     * Removes the specified role from all accounts of the given user and potentially carries out further actions which are needed to
     * properly reflect these changes.
     *
     * @param User $user The user to remove roles from
     * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the Neos.Neos namespace
     * @return integer How often this role has been removed from accounts owned by the user
     * @api
     */
    public function removeRoleFromUser(User $user, $roleIdentifier)
    {
        $counter = 0;
        foreach ($user->getAccounts() as $account) {
            $counter += $this->removeRoleFromAccount($account, $roleIdentifier);
        }

        return $counter;
    }

    /**
     * Signals that the given user data has been updated.
     *
     * @param User $user The created user
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitUserUpdated(User $user)
    {
    }

    /**
     * Overrides any assigned roles of the given account and potentially carries out further actions which are needed
     * to properly reflect these changes.
     *
     * @param Account $account The account to assign the roles to
     * @param array $newRoleIdentifiers A list of fully qualified role identifiers, or role identifiers relative to the Neos.Neos namespace
     * @return void
     * @api
     */
    public function setRolesForAccount(Account $account, array $newRoleIdentifiers)
    {
        $currentRoles = $account->getRoles();

        foreach ($currentRoles as $roleIdentifier => $role) {
            $roleIdentifier = $this->normalizeRoleIdentifier($roleIdentifier);
            if (!in_array($roleIdentifier, $newRoleIdentifiers)) {
                $this->removeRoleFromAccount($account, $roleIdentifier);
            }
        }

        foreach ($newRoleIdentifiers as $roleIdentifier) {
            if (!in_array($roleIdentifier, array_keys($currentRoles))) {
                $this->addRoleToAccount($account, $roleIdentifier);
            }
        }
    }

    /**
     * Adds the specified role to the given account and potentially carries out further actions which are needed to
     * properly reflect these changes.
     *
     * @param Account $account The account to add roles to
     * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the Neos.Neos namespace
     * @return integer How often this role has been added to the given account (effectively can be 1 or 0)
     * @api
     */
    public function addRoleToAccount(Account $account, $roleIdentifier)
    {
        $roleIdentifier = $this->normalizeRoleIdentifier($roleIdentifier);
        $role = $this->policyService->getRole($roleIdentifier);

        if (!$account->hasRole($role)) {
            $account->addRole($role);
            $this->accountRepository->update($account);
            $this->emitRolesAdded($account, array($role));

            return 1;
        }

        return 0;
    }

    /**
     * Signals that new roles have been assigned to the given account
     *
     * @param Account $account The account
     * @param array<Role> An array of Role objects which have been added for that account
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitRolesAdded(Account $account, array $roles)
    {
    }

    /**
     * Removes the specified role from the given account and potentially carries out further actions which are needed to
     * properly reflect these changes.
     *
     * @param Account $account The account to remove roles from
     * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the Neos.Neos namespace
     * @return integer How often this role has been removed from the given account (effectively can be 1 or 0)
     * @api
     */
    public function removeRoleFromAccount(Account $account, $roleIdentifier)
    {
        $roleIdentifier = $this->normalizeRoleIdentifier($roleIdentifier);
        $role = $this->policyService->getRole($roleIdentifier);

        /** @var Account $account */
        if ($account->hasRole($role)) {
            $account->removeRole($role);
            $this->accountRepository->update($account);
            $this->emitRolesRemoved($account, array($role));

            return 1;
        }

        return 0;
    }

    /**
     * Signals that roles have been removed to the given account
     *
     * @param Account $account The account
     * @param array<Role> An array of Role objects which have been removed
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitRolesRemoved(Account $account, array $roles)
    {
    }

    /**
     * Reactivates the given user
     *
     * @param User $user The user to deactivate
     * @return void
     * @api
     */
    public function activateUser(User $user)
    {
        foreach ($user->getAccounts() as $account) {
            /** @var Account $account */
            $account->setExpirationDate(null);
            $this->accountRepository->update($account);
        }
        $this->emitUserActivated($user);
    }

    /**
     * Signals that the given user has been activated
     *
     * @param User $user The user
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitUserActivated(User $user)
    {
    }

    /**
     * Deactivates the given user
     *
     * @param User $user The user to deactivate
     * @return void
     * @api
     */
    public function deactivateUser(User $user)
    {
        /** @var Account $account */
        foreach ($user->getAccounts() as $account) {
            $account->setExpirationDate($this->now);
            $this->accountRepository->update($account);
        }
        $this->emitUserDeactivated($user);
    }

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
        if ($workspace->getName() === 'live') {
            return $this->securityContext->hasRole('Neos.Neos:LivePublisher');
        }

        if ($workspace->getOwner() === $this->getCurrentUser() || $workspace->getOwner() === null) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the current user may read the given workspace according to one the roles of the user's accounts
     *
     * In future versions, this logic may be implemented in Neos in a more generic way (for example, by means of an
     * ACL object), but for now, this method exists in order to at least centralize and encapsulate the required logic.
     *
     * @param Workspace $workspace The workspace
     * @return boolean
     */
    public function currentUserCanReadWorkspace(Workspace $workspace)
    {
        if ($workspace->getName() === 'live') {
            return true;
        }

        if ($workspace->getOwner() === $this->getCurrentUser() || $workspace->getOwner() === null) {
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
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces');
        }

        if ($workspace->isPrivateWorkspace() && $workspace->getOwner() === $this->getCurrentUser()) {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.Module.Management.Workspaces.ManageOwnWorkspaces');
        }

        if ($workspace->isPrivateWorkspace() && $workspace->getOwner() !== $this->getCurrentUser()) {
            return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.Module.Management.Workspaces.ManageAllPrivateWorkspaces');
        }

        return false;
    }

    /**
     * Checks if the current user may transfer ownership of the given workspace
     *
     * In future versions, this logic may be implemented in Neos in a more generic way (for example, by means of an
     * ACL object), but for now, this method exists in order to at least centralize and encapsulate the required logic.
     *
     * @param Workspace $workspace The workspace
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
        return $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces');
    }

    /**
     * Returns the default authentication provider name
     *
     * @return string
     * @api
     */
    public function getDefaultAuthenticationProviderName()
    {
        return $this->defaultAuthenticationProviderName;
    }

    /**
     * Signals that the given user has been activated
     *
     * @param User $user The user
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitUserDeactivated(User $user)
    {
    }

    /**
     * Replaces role identifiers not containing a "." into fully qualified role identifiers from the Neos.Neos namespace.
     *
     * @param array $roleIdentifiers
     * @return array
     */
    protected function normalizeRoleIdentifiers(array $roleIdentifiers)
    {
        foreach ($roleIdentifiers as &$roleIdentifier) {
            $roleIdentifier = $this->normalizeRoleIdentifier($roleIdentifier);
        }

        return $roleIdentifiers;
    }

    /**
     * Replaces a role identifier not containing a "." into fully qualified role identifier from the Neos.Neos namespace.
     *
     * @param string $roleIdentifier
     * @return string
     * @throws NoSuchRoleException
     */
    protected function normalizeRoleIdentifier($roleIdentifier)
    {
        if (strpos($roleIdentifier, ':') === false) {
            $roleIdentifier = 'Neos.Neos:' . $roleIdentifier;
        }
        if (!$this->policyService->hasRole($roleIdentifier)) {
            throw new NoSuchRoleException(sprintf('The role %s does not exist.', $roleIdentifier), 1422540184);
        }

        return $roleIdentifier;
    }

    /**
     * Returns an array with all roles of a user's accounts, including parent roles, the "Everybody" role and the
     * "AuthenticatedUser" role, assuming that the user is logged in.
     *
     * @param User $user The user
     * @return array
     */
    protected function getAllRoles(User $user)
    {
        $roles = array(
            'Neos.Flow:Everybody' => $this->policyService->getRole('Neos.Flow:Everybody'),
            'Neos.Flow:AuthenticatedUser' => $this->policyService->getRole('Neos.Flow:AuthenticatedUser')
        );

        /** @var Account $account */
        foreach ($user->getAccounts() as $account) {
            $accountRoles = $account->getRoles();
            /** @var $currentRole Role */
            foreach ($accountRoles as $currentRole) {
                if (!in_array($currentRole, $roles)) {
                    $roles[$currentRole->getIdentifier()] = $currentRole;
                }
                /** @var $currentParentRole Role */
                foreach ($currentRole->getAllParentRoles() as $currentParentRole) {
                    if (!in_array($currentParentRole, $roles)) {
                        $roles[$currentParentRole->getIdentifier()] = $currentParentRole;
                    }
                }
            }
        }

        return $roles;
    }

    /**
     * Creates a personal workspace for the given user's account if it does not exist already.
     *
     * @param User $user The new user to create a workspace for
     * @param Account $account The user's backend account
     * @throws IllegalObjectTypeException
     */
    protected function createPersonalWorkspace(User $user, Account $account)
    {
        $userWorkspaceName = UserUtility::getPersonalWorkspaceNameForUsername($account->getAccountIdentifier());
        $userWorkspace = $this->workspaceRepository->findByIdentifier($userWorkspaceName);
        if ($userWorkspace === null) {
            $liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
            if (!($liveWorkspace instanceof Workspace)) {
                $liveWorkspace = new Workspace('live');
                $liveWorkspace->setTitle('Live');
                $this->workspaceRepository->add($liveWorkspace);
            }

            $userWorkspace = new Workspace($userWorkspaceName, $liveWorkspace, $user);
            $userWorkspace->setTitle((string)$user->getName());
            $this->workspaceRepository->add($userWorkspace);
        }
    }

    /**
     * Removes all personal workspaces of the given user's account if these workspaces exist. Also removes
     * all possibly existing content of these workspaces.
     *
     * @param string $accountIdentifier Identifier of the user's account
     * @return void
     */
    protected function deletePersonalWorkspace($accountIdentifier)
    {
        $userWorkspace = $this->workspaceRepository->findByIdentifier(UserUtility::getPersonalWorkspaceNameForUsername($accountIdentifier));
        if ($userWorkspace instanceof Workspace) {
            $this->publishingService->discardAllNodes($userWorkspace);
            $this->workspaceRepository->remove($userWorkspace);
        }
    }

    /**
     * Removes ownership of all workspaces currently owned by the given user
     *
     * @param User $user The user currently owning workspaces
     * @return void
     */
    protected function removeOwnerFromUsersWorkspaces(User $user)
    {
        /** @var Workspace $workspace */
        foreach ($this->workspaceRepository->findByOwner($user) as $workspace) {
            $workspace->setOwner(null);
            $this->workspaceRepository->update($workspace);
        }
    }
}
