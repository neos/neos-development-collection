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
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Factory\UserFactory;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Party\Domain\Model\ElectronicAddress;
use TYPO3\Party\Domain\Repository\PartyRepository;

/**
 * The TYPO3 User Admin module controller that allows for managing Neos backend users
 */
class UsersController extends AbstractModuleController {

	/**
	 * @Flow\Inject
	 * @var AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var PartyRepository
	 */
	protected $partyRepository;

	/**
	 * @Flow\Inject
	 * @var UserFactory
	 */
	protected $userFactory;

	/**
	 * @Flow\Inject
	 * @var HashService
	 */
	protected $hashService;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var PolicyService
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * The currently authenticated account (= backend user)
	 *
	 * @var Account
	 */
	protected $currentAccount;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
		if ($this->arguments->hasArgument('account')) {
			$propertyMappingConfigurationForAccount = $this->arguments->getArgument('account')->getPropertyMappingConfiguration();
			$propertyMappingConfigurationForAccountParty = $propertyMappingConfigurationForAccount->forProperty('party');
			$propertyMappingConfigurationForAccountPartyName = $propertyMappingConfigurationForAccount->forProperty('party.name');
			$propertyMappingConfigurationForAccountParty->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, '\TYPO3\Neos\Domain\Model\User');
			/** @var PropertyMappingConfiguration $propertyMappingConfiguration */
			foreach (array($propertyMappingConfigurationForAccountParty, $propertyMappingConfigurationForAccountPartyName) as $propertyMappingConfiguration) {
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
				$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			}
		}
		$this->currentAccount = $this->securityContext->getAccount();
	}

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assignMultiple(array(
			'currentAccount' => $this->currentAccount,
			'accounts' => $this->accountRepository->findByAuthenticationProviderName('Typo3BackendProvider')
		));
	}

	/**
	 * @param Account $account
	 * @return void
	 */
	public function showAction(Account $account) {
		$this->view->assignMultiple(array(
			'account' => $account,
			'currentAccount' => $this->currentAccount
		));
	}

	/**
	 * @param Account $account
	 * @return void
	 */
	public function newAction(Account $account = NULL) {
		$this->view->assignMultiple(array(
			'account' => $account,
			'roles' => $this->policyService->getRoles(),
		));
	}

	/**
	 * @param Account $account The account to create
	 * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
	 * @Flow\Validate(argumentName="account.accountIdentifier", type="\TYPO3\Neos\Validation\Validator\AccountExistsValidator")
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=0, "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Security
	 */
	public function createAction(Account $account, array $password) {
		/** @var array $password */
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$account->setCredentialsSource($this->hashService->hashPassword($password));
		}

		$this->partyRepository->add($account->getParty());
		$this->accountRepository->add($account);

		$this->addFlashMessage('The user account "%s" has been created.', 'Account added', Message::SEVERITY_OK, array($account->getAccountIdentifier()), 1416225561);
		$this->redirect('index');
	}

	/**
	 * @param Account $account
	 * @return void
	 */
	public function editAction(Account $account) {
		$this->assignElectronicAddressOptions();

		$this->view->assignMultiple(array(
			'account' => $account,
			'roles' => $this->policyService->getRoles(),
		));
	}

	/**
	 * @param Account $account
	 * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Security
	 */
	public function updateAction(Account $account, array $password = array()) {
		if ($account === $this->currentAccount && !$this->privilegeManager->isPrivilegeTargetGrantedForRoles($this->currentAccount->getRoles(), 'TYPO3.Neos:Backend.Module.Administration.Users')) {
			$this->addFlashMessage('With the selected roles the currently logged in user wouldn\'t have access to this module any longer. Please adjust the assigned roles!', 'Don\'t lock yourself out', Message::SEVERITY_WARNING, array(), 1416501197);
			$this->forward('edit', NULL, NULL, array('account' => $account));
		}
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$account->setCredentialsSource($this->hashService->hashPassword($password, 'default'));
		}

		$this->accountRepository->update($account);
		$this->partyRepository->update($account->getParty());

		$this->addFlashMessage('The user account "%s" has been updated.', 'Account updated', Message::SEVERITY_OK, array($account->getAccountIdentifier()), 1412374498);
		$this->redirect('index');
	}

	/**
	 * @param Account $account
	 * @return void
	 * @todo Security
	 */
	public function deleteAction(Account $account) {
		if ($account === $this->currentAccount) {
			$this->addFlashMessage('You can not delete the currently logged in user', 'Current account can\'t be removed', Message::SEVERITY_WARNING, array(), 1412374546);
			$this->redirect('index');
		}
		$this->accountRepository->remove($account);
		$this->addFlashMessage('The user account "%s" has been deleted.', 'Account removed', Message::SEVERITY_NOTICE, array($account->getAccountIdentifier()), 1412374546);
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
	 * @param ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function createElectronicAddressAction(Account $account, ElectronicAddress $electronicAddress) {
		/** @var User $user */
		$user = $account->getParty();
		$user->addElectronicAddress($electronicAddress);
		$this->partyRepository->update($user);
		$this->addFlashMessage('An electronic address "%s" (%s) has been added.', 'Electronic address added', Message::SEVERITY_OK, array($electronicAddress->getIdentifier(), $electronicAddress->getType()), 1412374814);
		$this->redirect('edit', NULL, NULL, array('account' => $account));
	}

	/**
	 * Delete an electronic address action
	 *
	 * @param Account $account
	 * @param ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function deleteElectronicAddressAction(Account $account, ElectronicAddress $electronicAddress) {
		/** @var User $user */
		$user = $account->getParty();
		$user->removeElectronicAddress($electronicAddress);
		$this->partyRepository->update($user);
		$this->addFlashMessage('The electronic address "%s" (%s) has been deleted for "%s".', 'Electronic address removed', Message::SEVERITY_NOTICE, array($electronicAddress->getIdentifier(), $electronicAddress->getType(), $user->getName()), 1412374678);
		$this->redirect('edit', NULL, NULL, array('account' => $account));
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
