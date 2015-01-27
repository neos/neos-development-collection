<?php
namespace TYPO3\Neos\Controller\Module\User;

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
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Party\Domain\Model\ElectronicAddress;

/**
 * The Neos User Settings module controller
 *
 * @Flow\Scope("singleton")
 */
class UserSettingsController extends AbstractModuleController {


	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

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
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			}
		}
		$this->currentUser = $this->userService->getCurrentUser();
	}

	/**
	 * Index
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->forward('edit');
	}

	/**
	 * Edit settings of the current user
	 *
	 * @return void
	 */
	public function editAction() {
		$this->assignElectronicAddressOptions();

		$this->view->assignMultiple(array(
			'user' => $this->currentUser
		));
	}

	/**
	 * Update the current user
	 *
	 * @param User $user The user to update, including updated data already (name, email address etc)
	 * @return void
	 */
	public function updateAction(User $user) {
		$this->userService->updateUser($user);
		$this->addFlashMessage('Your user has been updated.', 'User updated', Message::SEVERITY_OK);
		$this->redirect('edit');
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
			'user' => $this->userService->getUser($account->getAccountIdentifier(), $account->getAuthenticationProviderName())
		));
	}

	/**
	 * Update a given account, ie. the password
	 *
	 * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 */
	public function updateAccountAction(array $password = array()) {
		$user = $this->currentUser;
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$this->userService->setUserPassword($user, $password);
			$this->addFlashMessage('The password has been updated.', 'Password updated', Message::SEVERITY_OK);
		}
		$this->redirect('index');
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
