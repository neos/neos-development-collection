<?php
namespace TYPO3\Neos\Controller\Module\Administration;

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
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Party\Domain\Model\ElectronicAddress;

/**
 * The Neos User Admin module controller that allows for managing Neos users
 */
class UsersController extends AbstractModuleController {

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * @Flow\Inject
	 * @var PolicyService
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var UserService
	 */
	protected $userService;

	/**
	 * @var User
	 */
	protected $currentUser;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
		if ($this->arguments->hasArgument('user')) {
			$propertyMappingConfigurationForUser = $this->arguments->getArgument('user')->getPropertyMappingConfiguration();
			$propertyMappingConfigurationForUserName = $propertyMappingConfigurationForUser->forProperty('user.name');
			$propertyMappingConfigurationForPrimaryAccount = $propertyMappingConfigurationForUser->forProperty('user.primaryAccount');
			$propertyMappingConfigurationForPrimaryAccount->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, '\TYPO3\Flow\Security\Account');
			/** @var PropertyMappingConfiguration $propertyMappingConfiguration */
			foreach (array($propertyMappingConfigurationForUser, $propertyMappingConfigurationForUserName, $propertyMappingConfigurationForPrimaryAccount) as $propertyMappingConfiguration) {
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			}
		}
		$this->currentUser = $this->userService->getCurrentUser();
	}

	/**
	 * Shows a list of all users
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->view->assignMultiple(array(
			'currentUser' => $this->currentUser,
			'users' => $this->userService->getUsers()
		));
	}

	/**
	 * Shows details for the specified user
	 *
	 * @param User $user
	 * @return void
	 */
	public function showAction(User $user) {
		$this->view->assignMultiple(array(
			'currentUser' => $this->currentUser,
			'user' => $user
		));
	}

	/**
	 * Renders a form for creating a new user
	 *
	 * @param User $user
	 * @return void
	 */
	public function newAction(User $user = NULL) {
		$this->view->assignMultiple(array(
			'currentUser' => $this->currentUser,
			'user' => $user,
			'roles' => $this->policyService->getRoles()
		));
	}

	/**
	 * Create a new user
	 *
	 * @param string $username The user name (ie. account identifier) of the new user
	 * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
	 * @param User $user The user to create
	 * @param array $roleIdentifiers A list of roles (role identifiers) to assign to the new user
	 * @Flow\Validate(argumentName="username", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
	 * @Flow\Validate(argumentName="username", type="\TYPO3\Neos\Validation\Validator\UserDoesNotExistValidator")
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=0, "minimum"=1, "maximum"=255 })
	 * @return void
	 */
	public function createAction($username, array $password, User $user, array $roleIdentifiers) {
		$this->userService->addUser($username, $password[0], $user, $roleIdentifiers);
		$this->addFlashMessage('The user "%s" has been created.', 'User created', Message::SEVERITY_OK, array($username), 1416225561);
		$this->redirect('index');
	}

	/**
	 * Edit an existing user
	 *
	 * @param User $user
	 * @return void
	 */
	public function editAction(User $user) {
		$this->assignElectronicAddressOptions();

		$this->view->assignMultiple(array(
			'user' => $user,
			'availableRoles' => $this->policyService->getRoles()
		));
	}

	/**
	 * Update a given user
	 *
	 * @param User $user The user to update, including updated data already (name, email address etc)
	 * @return void
	 */
	public function updateAction(User $user) {
		$this->userService->updateUser($user);
		$this->addFlashMessage('The user "%s" has been updated.', 'User updated', Message::SEVERITY_OK, array($user->getName()->getFullName()), 1412374498);
		$this->redirect('index');
	}

	/**
	 * Delete the given user
	 *
	 * @param User $user
	 * @return void
	 */
	public function deleteAction(User $user) {
		if ($user === $this->currentUser) {
			$this->addFlashMessage('You can not delete the currently logged in user', 'Current user can\'t be deleted', Message::SEVERITY_WARNING, array(), 1412374546);
			$this->redirect('index');
		}
		$this->userService->deleteUser($user);
		$this->addFlashMessage('The user "%s" has been deleted.', 'User deleted', Message::SEVERITY_NOTICE, array($user->getName()->getFullName()), 1412374546);
		$this->redirect('index');
	}

	/**
	 * Edit the given account
	 *
	 * @param Account $account
	 * @return void
	 */
	public function editAccountAction(Account $account) {
		$this->view->assignMultiple(array(
			'account' => $account,
			'user' => $this->userService->getUser($account->getAccountIdentifier(), $account->getAuthenticationProviderName()),
			'availableRoles' => $this->policyService->getRoles()
		));
	}

	/**
	 * Update a given account
	 *
	 * @param Account $account The account to update
	 * @param array $roleIdentifiers A possibly updated list of roles for the user's primary account
	 * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 */
	public function updateAccountAction(Account $account, array $roleIdentifiers, array $password = array()) {
		$user = $this->userService->getUser($account->getAccountIdentifier(), $account->getAuthenticationProviderName());
		if ($user === $this->currentUser) {
			$roles = array();
			foreach ($roleIdentifiers as $roleIdentifier) {
				$roles[$roleIdentifier] = $this->policyService->getRole($roleIdentifier);
			}
			if (!$this->privilegeManager->isPrivilegeTargetGrantedForRoles($roles, 'TYPO3.Neos:Backend.Module.Administration.Users')) {
				$this->addFlashMessage('With the selected roles the currently logged in user wouldn\'t have access to this module any longer. Please adjust the assigned roles!', 'Don\'t lock yourself out', Message::SEVERITY_WARNING, array(), 1416501197);
				$this->forward('edit', NULL, NULL, array('user' => $this->currentUser));
			}
		}
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$this->userService->setUserPassword($user, $password);
		}

		$this->userService->setRolesForAccount($account, $roleIdentifiers);
		$this->addFlashMessage('The account has been updated.', 'Account updated', Message::SEVERITY_OK);
		$this->redirect('edit', NULL, NULL, array('user' => $user));
	}

	/**
	 * The add new electronic address action
	 *
	 * @param User $user
	 * @Flow\IgnoreValidation("$user")
	 * @return void
	 */
	public function newElectronicAddressAction(User $user) {
		$this->assignElectronicAddressOptions();
		$this->view->assign('user', $user);
	}

	/**
	 * Create an new electronic address
	 *
	 * @param User $user
	 * @param ElectronicAddress $electronicAddress
	 * @return void
	 */
	public function createElectronicAddressAction(User $user, ElectronicAddress $electronicAddress) {
		/** @var User $user */
		$user->addElectronicAddress($electronicAddress);
		$this->userService->updateUser($user);

		$this->addFlashMessage('An electronic address "%s" (%s) has been added.', 'Electronic address added', Message::SEVERITY_OK, array($electronicAddress->getIdentifier(), $electronicAddress->getType()), 1412374814);
		$this->redirect('edit', NULL, NULL, array('user' => $user));
	}

	/**
	 * Delete an electronic address action
	 *
	 * @param User $user
	 * @param ElectronicAddress $electronicAddress
	 * @return void
	 */
	public function deleteElectronicAddressAction(User $user, ElectronicAddress $electronicAddress) {
		$user->removeElectronicAddress($electronicAddress);
		$this->userService->updateUser($user);

		$this->addFlashMessage('The electronic address "%s" (%s) has been deleted for "%s".', 'Electronic address removed', Message::SEVERITY_NOTICE, array($electronicAddress->getIdentifier(), $electronicAddress->getType(), $user->getName()), 1412374678);
		$this->redirect('edit', NULL, NULL, array('user' => $user));
	}

	/**
	 *  @return void
	 */
	protected function assignElectronicAddressOptions() {
		$electronicAddress = new ElectronicAddress();
		$electronicAddressTypes = array();
		foreach ($electronicAddress->getAvailableElectronicAddressTypes() as $type) {
			$electronicAddressTypes[$type] = $type;
		}
		$electronicAddressUsageTypes = array();
		foreach ($electronicAddress->getAvailableUsageTypes() as $type) {
			$electronicAddressUsageTypes[$type] = $type;
		}
		array_unshift($electronicAddressUsageTypes, '');
		$this->view->assignMultiple(array(
			'electronicAddressTypes' => $electronicAddressTypes,
			'electronicAddressUsageTypes' => $electronicAddressUsageTypes
		));
	}
}
