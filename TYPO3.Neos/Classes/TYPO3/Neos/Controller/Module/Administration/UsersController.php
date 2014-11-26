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
		foreach ($this->accountRepository->findByAuthenticationProviderName('Typo3BackendProvider') as $account) {
			$accounts[$this->persistenceManager->getIdentifierByObject($account)] = $account;
		}
		$this->view->assignMultiple(array(
			'currentAccount' => $this->securityContext->getAccount(),
			'accounts' => $accounts
		));
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function newAction(\TYPO3\Flow\Security\Account $account = NULL) {
		$this->view->assign('account', $account);
		$this->view->assign('neosRoles', $this->getNeosRoles());
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
	 * @return void
	 * @todo Security
	 */
	public function createAction($identifier, array $password, $firstName, $lastName, $roleIdentifier) {
		$password = array_shift($password);

		$user = $this->userFactory->create($identifier, $password, $firstName, $lastName, array($roleIdentifier));

		$this->partyRepository->add($user);
		$accounts = $user->getAccounts();
		foreach ($accounts as $account) {
			$this->accountRepository->add($account);
		}

		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function editAction(\TYPO3\Flow\Security\Account $account) {
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
			'person' => $account->getParty(),
			'neosRoles' => $this->getNeosRoles(),
			'currentRole' => $currentRole
		));
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function showAction(\TYPO3\Flow\Security\Account $account) {
		$this->view->assign('account', $account);
		$this->view->assign('currentAccount', $this->securityContext->getAccount());
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @param \TYPO3\Party\Domain\Model\Person $person
	 * @param array $password
	 * @param string $roleIdentifier The role indentifier of the role this user should have
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Handle validation errors for account (accountIdentifier) & check if there's another account with the same accountIdentifier when changing it
	 * @todo Security
	 */
	public function updateAction(\TYPO3\Flow\Security\Account $account, \TYPO3\Party\Domain\Model\Person $person, array $password = array(), $roleIdentifier) {
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$account->setCredentialsSource($this->hashService->hashPassword($password, 'default'));
		}

		$account->setRoles(array($this->policyService->getRole($roleIdentifier)));

		$this->accountRepository->update($account);
		$this->partyRepository->update($person);

		$this->addFlashMessage('The user profile has been updated.');
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 * @todo Security
	 */
	public function deleteAction(\TYPO3\Flow\Security\Account $account) {
		if ($this->securityContext->getAccount() === $account) {
			$this->addFlashMessage('You can not remove current logged in user');
			$this->redirect('index');
		}
		$this->accountRepository->remove($account);
		$this->addFlashMessage('The user has been deleted.');
		$this->redirect('index');
	}

	/**
	 * The add new electronic address action
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @Flow\IgnoreValidation("$account")
	 * @return void
	 */
	public function newElectronicAddressAction(\TYPO3\Flow\Security\Account $account) {
		$this->assignElectronicAddressOptions();
		$this->view->assign('account', $account);
	}

	/**
	 * Create an new electronic address
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @param \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function createElectronicAddressAction(\TYPO3\Flow\Security\Account $account, \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress) {
		$party = $account->getParty();
		$party->addElectronicAddress($electronicAddress);
		$this->partyRepository->update($party);
		$this->addFlashMessage(sprintf(
			'An electronic "%s" address has been added.',
			$electronicAddress->getType() . ' (' . $electronicAddress->getIdentifier() . ')'
		));
		$this->redirect('edit', NULL, NULL, array('account' => $account));
	}

	/**
	 * Delete an electronic address action
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @param \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function deleteElectronicAddressAction(\TYPO3\Flow\Security\Account $account, \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress) {
		$party = $account->getParty();
		$party->removeElectronicAddress($electronicAddress);
		$this->partyRepository->update($party);
		$this->addFlashMessage(sprintf(
			'The electronic address "%s" has been deleted for the person "%s".',
			$electronicAddress->getType() . ' (' . $electronicAddress->getIdentifier() . ')',
			$party->getName()
		));
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
