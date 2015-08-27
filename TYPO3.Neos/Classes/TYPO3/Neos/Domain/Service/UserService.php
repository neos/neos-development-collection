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
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountFactory;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\Token\UsernamePassword;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Security\Exception\NoSuchRoleException;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Utility\Now;
use TYPO3\Neos\Domain\Exception;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Repository\UserRepository;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\Party\Domain\Model\PersonName;
use TYPO3\Party\Domain\Repository\PartyRepository;
use TYPO3\Party\Domain\Service\PartyService;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * A service for managing users
 *
 * @Flow\Scope("singleton")
 * @api
 */
class UserService {

	/**
	 * Might be configurable in the future, for now centralising this as a "constant"
	 *
	 * @var string
	 */
	protected $defaultAuthenticationProviderName = 'Typo3BackendProvider';

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
	 * Retrieves a list of all existing users
	 *
	 * @return array<User> The users
	 * @api
	 */
	public function getUsers() {
		return $this->userRepository->findAll();
	}

	/**
	 * Retrieves an existing user by the given username
	 *
	 * @param string $username The username
	 * @param string $authenticationProviderName Name of the authentication provider to use. Example: "Typo3BackendProvider"
	 * @return User The user, or NULL if the user does not exist
	 * @throws Exception
	 * @api
	 */
	public function getUser($username, $authenticationProviderName = NULL) {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProviderName ?: $this->defaultAuthenticationProviderName);
		if (!$account instanceof Account) {
			return NULL;
		}
		$user = $this->partyService->getAssignedPartyOfAccount($account);
		if (!$user instanceof User) {
			throw new Exception(sprintf('Unexpected user type "%s". An account with the identifier "%s" exists, but the corresponding party is not a Neos User.', get_class($user), $username), 1422270948);
		}
		return $user;
	}

	/**
	 * Returns the currently logged in user, if any
	 *
	 * @return User The currently logged in user, or NULL
	 * @api
	 */
	public function getCurrentUser() {
		$account = $this->securityContext->getAccount();
		if ($account !== NULL) {
			return $this->getUser($account->getAccountIdentifier());
		} else {
			return NULL;
		}
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
	 * @param string $authenticationProviderName Name of the authentication provider to use. Example: "Typo3BackendProvider"
	 * @return User The created user instance
	 * @api
	 */
	public function createUser($username, $password, $firstName, $lastName, array $roleIdentifiers = NULL, $authenticationProviderName = NULL) {
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
	 * @param string $authenticationProviderName Name of the authentication provider to use. Example: "Typo3BackendProvider"
	 * @return User The same user object
	 * @api
	 */
	public function addUser($username, $password, User $user, array $roleIdentifiers = NULL, $authenticationProviderName = NULL) {
		if ($roleIdentifiers === NULL) {
			$roleIdentifiers = array('TYPO3.Neos:Editor');
		}
		$roleIdentifiers = $this->normalizeRoleIdentifiers($roleIdentifiers);
		$account = $this->accountFactory->createAccountWithPassword($username, $password, $roleIdentifiers, $authenticationProviderName ?: $this->defaultAuthenticationProviderName);
		$user->addAccount($account);

		$this->partyRepository->add($user);
		$this->accountRepository->add($account);

		$this->createUserWorkspace($username);

		$this->emitUserCreated($user);
		return $user;
	}

	/**
	 * Signals that a new user, including a new account has been created.
	 *
	 * @param User $user The created user
	 * @return void
	 * @Flow\Signal
	 */
	public function emitUserCreated(User $user) {}

	/**
	 * Deletes the specified user and all remaining content in his personal workspaces
	 *
	 * @param User $user The user to delete
	 * @return void
	 * @throws Exception
	 * @api
	 */
	public function deleteUser(User $user) {
		$backendUserRole = $this->policyService->getRole('TYPO3.Neos:Editor');

		foreach ($user->getAccounts() as $account) {
			/** @var Account $account */
			if ($account->hasRole($backendUserRole)) {
				$this->deleteUserWorkspaces($account->getAccountIdentifier());
			}
			$this->accountRepository->remove($account);
		}
		$this->partyRepository->remove($user);
		$this->emitUserDeleted($user);
	}

	/**
	 * Signals that the given user has been deleted.
	 *
	 * @param User $user The created user
	 * @return void
	 * @Flow\Signal
	 */
	public function emitUserDeleted(User $user) {}

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
	public function setUserPassword(User $user, $password) {
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
	public function updateUser(User $user) {
		$this->partyRepository->update($user);
		$this->emitUserUpdated($user);
	}

	/**
	 * Adds the specified role to all accounts of the given user and potentially carries out further actions which are needed to
	 * properly reflect these changes.
	 *
	 * @param User $user The user to add roles to
	 * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the TYPO3.Neos namespace
	 * @return integer How often this role has been added to accounts owned by the user
	 * @api
	 */
	public function addRoleToUser(User $user, $roleIdentifier) {
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
	 * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the TYPO3.Neos namespace
	 * @return integer How often this role has been removed from accounts owned by the user
	 * @api
	 */
	public function removeRoleFromUser(User $user, $roleIdentifier) {
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
	 */
	public function emitUserUpdated(User $user) {}

	/**
	 * Overrides any assigned roles of the given account and potentially carries out further actions which are needed
	 * to properly reflect these changes.
	 *
	 * @param Account $account The account to assign the roles to
	 * @param array $newRoleIdentifiers A list of fully qualified role identifiers, or role identifiers relative to the TYPO3.Neos namespace
	 * @return void
	 * @api
	 */
	public function setRolesForAccount(Account $account, array $newRoleIdentifiers) {
		$currentRoles = $account->getRoles();

		foreach($currentRoles as $roleIdentifier => $role) {
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
	 * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the TYPO3.Neos namespace
	 * @return integer How often this role has been added to the given account (effectively can be 1 or 0)
	 * @api
	 */
	public function addRoleToAccount(Account $account, $roleIdentifier) {
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
	 */
	public function emitRolesAdded(Account $account, array $roles) {}

	/**
	 * Removes the specified role from the given account and potentially carries out further actions which are needed to
	 * properly reflect these changes.
	 *
	 * @param Account $account The account to remove roles from
	 * @param string $roleIdentifier A fully qualified role identifier, or a role identifier relative to the TYPO3.Neos namespace
	 * @return integer How often this role has been removed from the given account (effectively can be 1 or 0)
	 * @api
	 */
	public function removeRoleFromAccount(Account $account, $roleIdentifier) {
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
	 */
	public function emitRolesRemoved(Account $account, array $roles) {}

	/**
	 * Reactivates the given user
	 *
	 * @param User $user The user to deactivate
	 * @return void
	 * @api
	 */
	public function activateUser(User $user) {
		foreach ($user->getAccounts() as $account) {
			/** @var Account $account */
			$account->setExpirationDate(NULL);
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
	 */
	public function emitUserActivated(User $user) {}

	/**
	 * Deactivates the given user
	 *
	 * @param User $user The user to deactivate
	 * @return void
	 * @api
	 */
	public function deactivateUser(User $user) {
		foreach ($user->getAccounts() as $account) {
			/** @var Account $account */
			$account->setExpirationDate($this->now);
			$this->accountRepository->update($account);
		}
		$this->emitUserDeactivated($user);
	}

	/**
	 * Returns the default authentication provider name
	 *
	 * @return string
	 */
	public function getDefaultAuthenticationProviderName() {
		return $this->defaultAuthenticationProviderName;
	}

	/**
	 * Signals that the given user has been activated
	 *
	 * @param User $user The user
	 * @return void
	 * @Flow\Signal
	 */
	public function emitUserDeactivated(User $user) {}

	/**
	 * Replaces role identifiers not containing a "." into fully qualified role identifiers from the TYPO3.Neos namespace.
	 *
	 * @param array $roleIdentifiers
	 * @return array
	 */
	protected function normalizeRoleIdentifiers(array $roleIdentifiers) {
		foreach ($roleIdentifiers as &$roleIdentifier) {
			$roleIdentifier = $this->normalizeRoleIdentifier($roleIdentifier);
		}
		return $roleIdentifiers;
	}

	/**
	 * Replaces a role identifier not containing a "." into fully qualified role identifier from the TYPO3.Neos namespace.
	 *
	 * @param string $roleIdentifier
	 * @return string
	 * @throws NoSuchRoleException
	 */
	protected function normalizeRoleIdentifier($roleIdentifier) {
		if (strpos($roleIdentifier, ':') === FALSE) {
			$roleIdentifier = 'TYPO3.Neos:' . $roleIdentifier;
		}
		if (!$this->policyService->hasRole($roleIdentifier)) {
			throw new NoSuchRoleException(sprintf('The role %s does not exist.', $roleIdentifier), 1422540184);
		}
		return $roleIdentifier;
	}

	/**
	 * Creates a user workspace for the given user's account if it does not exist already.
	 *
	 * @param string $accountIdentifier Identifier of the user's account to create workspaces for
	 * @return void
	 */
	protected function createUserWorkspace($accountIdentifier) {
		$userWorkspace = $this->workspaceRepository->findByIdentifier('user-' . $accountIdentifier);
		if ($userWorkspace === NULL) {
			$liveWorkspace = $this->workspaceRepository->findByIdentifier('live');
			if (!($liveWorkspace instanceof Workspace)) {
				$liveWorkspace = new Workspace('live');
				$this->workspaceRepository->add($liveWorkspace);
			}
			$owner = $this->getUser($accountIdentifier);

			$userWorkspace = new Workspace('user-' . $accountIdentifier, $liveWorkspace, $owner);
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
	protected function deleteUserWorkspaces($accountIdentifier) {
		$userWorkspace = $this->workspaceRepository->findByIdentifier('user-' . $accountIdentifier);
		if ($userWorkspace instanceof Workspace) {
			$this->publishingService->discardAllNodes($userWorkspace);
			$this->workspaceRepository->remove($userWorkspace);
		}
	}

}
