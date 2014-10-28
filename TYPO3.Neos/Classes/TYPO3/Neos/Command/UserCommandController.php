<?php
namespace TYPO3\Neos\Command;

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
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Party\Domain\Model\Person;
use TYPO3\Flow\Utility\Now;

/**
 * The User Command Controller
 *
 * @Flow\Scope("singleton")
 */
class UserCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Party\Domain\Repository\PartyRepository
	 */
	protected $partyRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Factory\UserFactory
	 */
	protected $userFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * List all users
	 *
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function listCommand($authenticationProvider = 'Typo3BackendProvider') {
		$accounts = $this->accountRepository->findByAuthenticationProviderName($authenticationProvider);

		$activeAccounts = array();
		$inactiveAccounts = array();
		foreach ($accounts as $account) {
			/** @var Account $account */
			$accountIdentifier = $account->getAccountIdentifier();
			$expirationDate = $account->getExpirationDate();
			if ($expirationDate === NULL || $expirationDate > new Now()) {
				$activeAccounts[$accountIdentifier] = $account;
			} else {
				$inactiveAccounts[$accountIdentifier] = $account;
			}
		}

		ksort($activeAccounts);
		ksort($inactiveAccounts);

		$this->outputLine('ACTIVE USERS:');
		foreach ($activeAccounts as $accountIdentifier => $account) {
			$this->outputLine(' ' . $accountIdentifier);
		}

		if (count($inactiveAccounts) > 0) {
			$this->outputLine();
			$this->outputLine('INACTIVE USERS:');
			foreach ($inactiveAccounts as $accountIdentifier => $account) {
				$this->outputLine(' ' . $accountIdentifier);
			}
		}
	}

	/**
	 * Create a new user
	 *
	 * This command creates a new user which has access to the backend user interface.
	 * It is recommended to user the email address as a username.
	 *
	 * @param string $username The username of the user to be created.
	 * @param string $password Password of the user to be created
	 * @param string $firstName First name of the user to be created
	 * @param string $lastName Last name of the user to be created
	 * @param string $roles A comma separated list of roles to assign
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function createCommand($username, $password, $firstName, $lastName, $roles = NULL, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if ($account instanceof Account) {
			$this->outputLine('The username "%s" is already in use', array($username));
			exit(1);
		}

		if (empty($roles)) {
			$roleIdentifiers = array('TYPO3.Neos:Editor');
		} else {
			$roleIdentifiers = \TYPO3\Flow\Utility\Arrays::trimExplode(',', $roles);
			foreach ($roleIdentifiers as &$role) {
				if (strpos($role, '.') === FALSE) {
					$role = 'TYPO3.Neos:' . $role;
				}
			}
		}

		try {
			$user = $this->userFactory->create($username, $password, $firstName, $lastName, $roleIdentifiers, $authenticationProvider);
			$this->partyRepository->add($user);
			$accounts = $user->getAccounts();
			foreach ($accounts as $account) {
				$this->accountRepository->add($account);
			}

			$this->outputLine('Created user "%s".', array($username));
		} catch (\TYPO3\Flow\Security\Exception\NoSuchRoleException $exception) {
			$this->outputLine($exception->getMessage());
			exit(1);
		}
	}

	/**
	 * Remove a user which has access to the backend user interface.
	 *
	 * @param string $username The username of the user to be removed.
	 * @param boolean $confirmation
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function removeCommand($username, $confirmation = FALSE, $authenticationProvider = 'Typo3BackendProvider') {
		if ($confirmation === FALSE) {
			$this->outputLine('Please confirm that you really want to remove the user from the database.');
			$this->outputLine('');
			$this->outputLine('Syntax:');
			$this->outputLine('  ./flow user:remove --username <username> --confirmation TRUE');
			exit(1);
		}

		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('The username "%s" is not in use', array($username));
			exit(1);
		}
		$this->accountRepository->remove($account);
		$this->outputLine('Removed user "%s".', array($username));
	}

	/**
	 * Activate a user which has access to the backend user interface.
	 *
	 * @param string $username The username of the user to be activated.
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function activateCommand($username, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('The username "%s" is not in use', array($username));
			exit(1);
		}
		$account->setExpirationDate(NULL);
		$this->accountRepository->update($account);
		$this->outputLine('Activated user "%s".', array($username));
	}

	/**
	 * Deactivate a user which has access to the backend user interface.
	 *
	 * @param string $username The username of the user to be deactivated.
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function deactivateCommand($username, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('The username "%s" is not in use', array($username));
			exit(1);
		}
		$account->setExpirationDate(new Now());
		$this->accountRepository->update($account);
		$this->outputLine('Deactivated user "%s".', array($username));
	}

	/**
	 * Set a new password for the given user
	 *
	 * This allows for setting a new password for an existing user account.
	 *
	 * @param string $username Username of the account to modify
	 * @param string $password The new password
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function setPasswordCommand($username, $password, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('User "%s" does not exists.', array($username));
			exit(1);
		}
		$account->setCredentialsSource($this->hashService->hashPassword($password, 'default'));
		$this->accountRepository->update($account);

		$this->outputLine('The new password for user "%s" was set.', array($username));
	}

	/**
	 * Add a role to a user
	 *
	 * This command allows for adding a specific role to an existing user.
	 * Currently supported roles: "TYPO3.Neos:Editor", "TYPO3.Neos:Administrator"
	 *
	 * @param string $username The username of the user
	 * @param string $role Role ot be added to the use
	 * @param string $authenticationProvider Name of the authentication provider to user
	 * @return void
	 */
	public function addRoleCommand($username, $role, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('User "%s" does not exists.', array($username));
			exit(1);
		}

		if (strpos($role, '.') === FALSE) {
			$role = 'TYPO3.Neos:' . $role;
		}
		$roleObject = $this->policyService->getRole($role);
		if ($roleObject === NULL) {
			$this->outputLine('Role "%s" does not exist.', array($role));
			exit(1);
		}

		if ($account->hasRole($roleObject)) {
			$this->outputLine('User "%s" already has the role "%s" assigned.', array($username, $role));
			exit(1);
		}

		$account->addRole($roleObject);
		$this->accountRepository->update($account);
		$this->outputLine('Added role "%s" to user "%s".', array($role, $username));
	}

	/**
	 * Remove a role from a user
	 *
	 * @param string $username The username of the user
	 * @param string $role Role ot be removed from the user
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function removeRoleCommand($username, $role, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('User "%s" does not exists.', array($username));
			exit(1);
		}

		if (strpos($role, '.') === FALSE) {
			$role = 'TYPO3.Neos:' . $role;
		}

		$roleObject = $this->policyService->getRole($role);
		if ($roleObject === NULL) {
			$this->outputLine('Role "%s" does not exist.', array($role));
			exit(1);
		}

		if (!$account->hasRole($roleObject)) {
			$this->outputLine('User "%s" does not have the role "%s" assigned.', array($username, $role));
			exit(1);
		}

		$account->removeRole($roleObject);
		$this->accountRepository->update($account);
		$this->outputLine('Removed role "%s" from user "%s".', array($role, $username));
	}

	/**
	 * Shows the given user
	 *
	 * This command shows some basic details about the given user. If such a user does not exist, this command
	 * will exit with a non-zero status code.
	 *
	 * @param string $username The username of the user to show.
	 * @param string $authenticationProvider Name of the authentication provider to use
	 * @return void
	 */
	public function showCommand($username, $authenticationProvider = 'Typo3BackendProvider') {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, $authenticationProvider);
		if (!$account instanceof Account) {
			$this->outputLine('The username "%s" is not in use', array($username));
			exit(1);
		}

		$roleNames = array();
		foreach ($account->getRoles() as $role) {
			/** @var Role $role */
			$roleNames[] = $role->getIdentifier();
		}

		$this->outputLine('Username:  %s', array($username));
		$this->outputLine('Roles:     %s', array(implode(', ', $roleNames)));

		$party = $account->getParty();
		if ($party instanceof Person) {
			$this->outputLine('Name:      %s', array($party->getName()->getFullName()));
			$this->outputLine('Email:     %s', array($party->getPrimaryElectronicAddress()));
		}
	}

}
