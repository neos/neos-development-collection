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
use TYPO3\Flow\Security\Account;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Model\UserPreferences;

/**
 * The TYPO3 User Admin module controller
 *
 * @Flow\Scope("singleton")
 */
class UsersController extends \TYPO3\Neos\Controller\Module\AbstractModuleController {

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
	 * @var \TYPO3\Neos\Domain\Factory\UserFactory
	 */
	protected $userFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		if ($this->arguments->hasArgument('account')) {
			$propertyMappingConfigurationForAccount = $this->arguments->getArgument('account')->getPropertyMappingConfiguration();
			$propertyMappingConfigurationForAccountParty = $propertyMappingConfigurationForAccount->forProperty('party');
			$propertyMappingConfigurationForAccountPartyName = $propertyMappingConfigurationForAccount->forProperty('party.name');
			$propertyMappingConfigurationForAccountParty->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, '\TYPO3\Neos\Domain\Model\User');
			foreach (array($propertyMappingConfigurationForAccountParty, $propertyMappingConfigurationForAccountPartyName) as $propertyMappingConfiguration) {
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			}
		}
	}

	/**
	 * @return void
	 */
	public function indexAction() {
		$accounts = array();
		foreach ($this->accountRepository->findAll() as $account) {
			$accounts[$this->persistenceManager->getIdentifierByObject($account)] = $account;
		}
		$this->view->assignMultiple(array(
			'currentAccount' => $this->securityContext->getAccount(),
			'accounts' => $accounts
		));
	}

	/**
	 * @param Account $account
	 * @return void
	 */
	public function newAction(Account $account = NULL) {
		$this->view->assignMultiple(array(
			'account' => $account,
			'neosRoles'=> $this->getNeosRoles()
		));
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
	}

	/**
	 * @param string $identifier
	 * @Flow\Validate(argumentName="identifier", type="NotEmpty")
	 * @Flow\Validate(argumentName="identifier", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @Flow\Validate(argumentName="identifier", type="\TYPO3\Neos\Validation\Validator\AccountExistsValidator", options={ "authenticationProviderName"="Typo3BackendProvider" })
	 * @param array $password
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=0, "minimum"=1, "maximum"=255 })
	 * @param string $firstName
	 * @Flow\Validate(argumentName="firstName", type="NotEmpty")
	 * @Flow\Validate(argumentName="firstName", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @param string $lastName
	 * @Flow\Validate(argumentName="lastName", type="NotEmpty")
	 * @Flow\Validate(argumentName="lastName", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @param string $roleIdentifier The role identifier of the role this user should have
	 * @param UserPreferences $userPreferences
	 * @return void
	 * @todo Security
	 */
	public function createAction($identifier, array $password, $firstName, $lastName, $roleIdentifier, UserPreferences $userPreferences = NULL) {
		/** @var array $password */
		$password = array_shift($password);

		/** @var User $user */
		$user = $this->userFactory->create($identifier, $password, $firstName, $lastName, array($roleIdentifier));

		$preferences = $user->getPreferences();
		if (!($preferences instanceof UserPreferences)) {
			$preferences = new UserPreferences();
		}

		foreach ($userPreferences->getPreferences() as $key => $value) {
			$preferences->set($key, $value);
		}

		$user->setPreferences($preferences);

		$this->partyRepository->add($user);
		$accounts = $user->getAccounts();
		foreach ($accounts as $account) {
			$this->accountRepository->add($account);
		}

		$this->redirect('index');
	}

	/**
	 * @param Account $account
	 * @return void
	 */
	public function editAction(Account $account) {
		$this->assignElectronicAddressOptions();

		$currentRole = NULL;
		foreach ($account->getRoles() as $role) {
			if ($role->getPackageKey() === 'TYPO3.Neos') {
				$currentRole = $role;
				break;
			}
		}

		$this->view->assignMultiple(array(
			'account' => $account,
			'user' => $account->getParty(),
			'neosRoles' => $this->getNeosRoles(),
			'currentRole' => $currentRole
		));
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
	}

	/**
	 * @param Account $account
	 * @return void
	 */
	public function showAction(Account $account) {
		$this->view->assign('account', $account);
		$this->view->assign('currentAccount', $this->securityContext->getAccount());
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
	}

	/**
	 * @param Account $account
	 * @param User $user
	 * @param array $password
	 * @param string $roleIdentifier The role indentifier of the role this user should have
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Handle validation errors for account (accountIdentifier) & check if there's another account with the same accountIdentifier when changing it
	 * @todo Security
	 */
	public function updateAction(Account $account, User $user, array $password = array(), $roleIdentifier) {
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$account->setCredentialsSource($this->hashService->hashPassword($password, 'default'));
		}

		$account->setRoles(array($this->policyService->getRole($roleIdentifier)));

		$this->accountRepository->update($account);
		$this->partyRepository->update($user);

		$this->addFlashMessage('The user profile has been updated.', NULL, NULL, array(), 1412374498);
		$this->redirect('index');
	}

	/**
	 * @param Account $account
	 * @return void
	 * @todo Security
	 */
	public function deleteAction(Account $account) {
		if ($this->securityContext->getAccount() === $account) {
			$this->addFlashMessage('You can not remove current logged in user', NULL, NULL, array(), 1412374546);
			$this->redirect('index');
		}
		$this->accountRepository->remove($account);
		$this->addFlashMessage('The user has been deleted.', NULL, NULL, array(), 1412374546);
		$this->redirect('index');
	}

	/**
	 * The add new electronic address action
	 *
	 * @param Account $account
	 * @Flow\IgnoreValidation("$account")
	 * @return void
	 */
	public function newElectronicAddressAction(Account $account) {
		$this->assignElectronicAddressOptions();
		$this->view->assign('account', $account);
	}

	/**
	 * Create an new electronic address
	 *
	 * @param Account $account
	 * @param \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function createElectronicAddressAction(Account $account, \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress) {
		$party = $account->getParty();
		$party->addElectronicAddress($electronicAddress);
		$this->partyRepository->update($party);
		$this->addFlashMessage('An electronic "%s" (%s) address has been added.', '', NULL, array($electronicAddress->getIdentifier(), $electronicAddress->getType()), 1412374814);
		$this->redirect('edit', NULL, NULL, array('account' => $account));
	}

	/**
	 * Delete an electronic address action
	 *
	 * @param Account $account
	 * @param \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function deleteElectronicAddressAction(Account $account, \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress) {
		$party = $account->getParty();
		$party->removeElectronicAddress($electronicAddress);
		$this->partyRepository->update($party);
		$this->addFlashMessage('The electronic address "%s" (%s) has been deleted for "%s".', '', NULL, array($electronicAddress->getIdentifier(), $electronicAddress->getType(), $party->getName()), 1412374678);
		$this->redirect('edit', NULL, NULL, array('account' => $account));
	}

	/**
	 *  @return void
	 */
	protected function assignElectronicAddressOptions() {
		$electronicAddress = new \TYPO3\Party\Domain\Model\ElectronicAddress();
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

	/**
	 * Returns all roles defined in the Neos package
	 *
	 * @return array<\TYPO3\Flow\Security\Policy\Role>
	 */
	protected function getNeosRoles() {
		$neosRoles = array();
		foreach ($this->policyService->getRoles() as $role) {
			if ($role->getPackageKey() === 'TYPO3.Neos') {
				$neosRoles[] = $role;
			}
		}
		return $neosRoles;
	}
}
