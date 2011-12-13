<?php
namespace TYPO3\TYPO3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TYPO3 Setup
 *
 * @FLOW3\Scope("singleton")
 */
class SetupCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\Party\Domain\Repository\PartyRepository
	 */
	protected $partyRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * Create users with the Administrator role.
	 *
	 * @param string $identifier Identifier (username) of the account to be created
	 * @param string $password Password of the account to be created
	 * @param string $firstName First name of the user to be created
	 * @param string $lastName Last name of the user to be created
	 * @return void
	 */
	public function createAdministratorCommand($identifier, $password, $firstName, $lastName) {
		$user = new \TYPO3\TYPO3\Domain\Model\User();
		$name = new \TYPO3\Party\Domain\Model\PersonName('', $firstName, '', $lastName, '', $identifier);
		$user->setName($name);
		$user->getPreferences()->set('context.workspace', 'user-' . $identifier);
		$this->partyRepository->add($user);

		$account = $this->accountFactory->createAccountWithPassword($identifier, $password, array('Administrator'), 'Typo3BackendProvider');
		$account->setParty($user);
		$this->accountRepository->add($account);
		$this->outputLine('Created account "%s".', array($identifier));
	}

}
?>