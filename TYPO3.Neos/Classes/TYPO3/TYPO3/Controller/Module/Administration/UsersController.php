<?php
namespace TYPO3\TYPO3\Controller\Module\Administration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
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
class UsersController extends \TYPO3\TYPO3\Controller\Module\StandardController {

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
	 * @var \TYPO3\TYPO3\Domain\Factory\UserFactory
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
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		if ($this->arguments->hasArgument('account')) {
			$propertyMappingConfigurationForAccount = $this->arguments->getArgument('account')->getPropertyMappingConfiguration();
			$propertyMappingConfigurationForAccountParty = $propertyMappingConfigurationForAccount->forProperty('party');
			$propertyMappingConfigurationForAccountPartyName = $propertyMappingConfigurationForAccount->forProperty('party.name');
			$propertyMappingConfigurationForAccountParty->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, '\TYPO3\TYPO3\Domain\Model\User');
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
		$this->view->assign('currentAccount', $this->securityContext->getAccount());
		$this->view->assign('accounts', $accounts);
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function newAction(\TYPO3\Flow\Security\Account $account = NULL) {
		$this->view->assign('account', $account);
		$this->setTitle($this->moduleConfiguration['label'] . ' :: ' . ucfirst($this->request->getControllerActionName()));
	}

	/**
	 * @param string $identifier
	 * @Flow\Validate(argumentName="identifier", type="NotEmpty")
	 * @Flow\Validate(argumentName="identifier", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @Flow\Validate(argumentName="identifier", type="\TYPO3\TYPO3\Validation\Validator\AccountExistsValidator", options={ "authenticationProviderName"="Typo3BackendProvider" })
	 * @param array $password
	 * @Flow\Validate(argumentName="password", type="\TYPO3\TYPO3\Validation\Validator\PasswordValidator", options={ "allowEmpty"=0, "minimum"=1, "maximum"=255 })
	 * @param string $firstName
	 * @Flow\Validate(argumentName="firstName", type="NotEmpty")
	 * @Flow\Validate(argumentName="firstName", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @param string $lastName
	 * @Flow\Validate(argumentName="lastName", type="NotEmpty")
	 * @Flow\Validate(argumentName="lastName", type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Security
	 */
	public function createAction($identifier, array $password, $firstName, $lastName) {
		$password = array_shift($password);
		$user = $this->userFactory->create($identifier, $password, $firstName, $lastName, array('Administrator'));

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
	 * @todo Creation/editing of electronic addresses on party property
	 */
	public function editAction(\TYPO3\Flow\Security\Account $account) {
		$this->view->assign('account', $account);
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
	 * @param array $password
	 * @Flow\Validate(argumentName="password", type="\TYPO3\TYPO3\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Handle validation errors for account (accountIdentifier) & check if there's another account with the same accountIdentifier when changing it
	 * @todo Security
	 */
	public function updateAction(\TYPO3\Flow\Security\Account $account, array $password = array()) {
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$account->setCredentialsSource($this->hashService->hashPassword($password, 'default'));
		}

		$this->accountRepository->update($account);
		$this->partyRepository->update($account->getParty());

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

}
?>