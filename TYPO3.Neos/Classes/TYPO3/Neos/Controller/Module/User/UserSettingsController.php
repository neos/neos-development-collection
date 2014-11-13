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
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Flow\Security\Account;
use TYPO3\Neos\Controller\Module\AbstractModuleController;

/**
 * The TYPO3 Neos User Settings module controller
 *
 * @Flow\Scope("singleton")
 */
class UserSettingsController extends AbstractModuleController {

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
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

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
		$this->assignElectronicAddressOptions();
		$account = $this->securityContext->getAccount();
		$this->view->assignMultiple(array(
			'account' => $account,
			'user' => $account->getParty()
		));
	}

	/**
	 * @param Account $account
	 * @param User $user
	 * @param array $password
	 * @Flow\Validate(argumentName="password", type="\TYPO3\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
	 * @return void
	 * @todo Handle validation errors for account (accountIdentifier) & check if there's another account with the same accountIdentifier when changing it
	 * @todo Security
	 */
	public function updateAction(Account $account, User $user, array $password = array()) {
		$password = array_shift($password);
		if (strlen(trim(strval($password))) > 0) {
			$account->setCredentialsSource($this->hashService->hashPassword($password, 'default'));
			$this->accountRepository->update($account);
		}

		$this->partyRepository->update($user);

		$this->addFlashMessage('The user profile has been updated.', NULL, NULL, array(), 1412375330);
		$this->redirect('index');
	}

	/**
	 * The add new electronic address action
	 *
	 * @return void
	 */
	public function newElectronicAddressAction() {
		$this->assignElectronicAddressOptions();
	}

	/**
	 * Create a new electronic address
	 *
	 * @param \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function createElectronicAddressAction(\TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress) {
		$party = $this->securityContext->getAccount()->getParty();
		$party->addElectronicAddress($electronicAddress);
		$this->partyRepository->update($party);
		$this->addFlashMessage('An electronic "%s" (%s) address has been added.', NULL, NULL, array($electronicAddress->getIdentifier(), $electronicAddress->getType()), 1412375475);
		$this->redirect('index');
	}

	/**
	 * Delete an electronic address action
	 *
	 * @param \TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress
	 * @return void
	 * @todo Security
	 */
	public function deleteElectronicAddressAction(\TYPO3\Party\Domain\Model\ElectronicAddress $electronicAddress) {
		$party = $this->securityContext->getAccount()->getParty();
		$party->removeElectronicAddress($electronicAddress);
		$this->partyRepository->update($party);
		$this->addFlashMessage('The electronic address "%s" (%s) has been deleted for "%s".', NULL, NULL, array($electronicAddress->getIdentifier(), $electronicAddress->getType(), $party->getName()), 1412375529);
		$this->redirect('index');
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
		$this->view->assignMultiple(array(
			'electronicAddressTypes' => $electronicAddressTypes,
			'electronicAddressUsageTypes' => $electronicAddressUsageTypes
		));
	}

}
