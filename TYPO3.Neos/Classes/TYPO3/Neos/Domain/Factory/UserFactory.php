<?php
namespace TYPO3\Neos\Domain\Factory;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\Party\Domain\Model\User;

use TYPO3\Flow\Annotations as Flow;

/**
 * A factory to conveniently create User models
 *
 * @Flow\Scope("singleton")
 */
class UserFactory {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * Creates a User with the given information
	 *
	 * The User is not added to the repository, the caller has to add the
	 * User account to the AccountRepository and the User to the
	 * PartyRepository to persist it.
	 *
	 * @param string $username The username of the user to be created.
	 * @param string $password Password of the user to be created
	 * @param string $firstName First name of the user to be created
	 * @param string $lastName Last name of the user to be created
	 * @param array $roleIdentifiers A list of role identifiers to assign
	 * @return \TYPO3\Neos\Domain\Model\User The created user instance
	 */
	public function create($username, $password, $firstName, $lastName, array $roleIdentifiers = NULL) {
		$user = new \TYPO3\Neos\Domain\Model\User();
		$name = new \TYPO3\Party\Domain\Model\PersonName('', $firstName, '', $lastName, '', $username);
		$user->setName($name);

		$workspaceName = 'user-' . preg_replace('/[^a-z0-9]/i', '', $username);
		$user->getPreferences()->set('context.workspace', $workspaceName);

		if ($roleIdentifiers === NULL || $roleIdentifiers === array()) {
			$roleIdentifiers = array('Editor');
		}

		$account = $this->accountFactory->createAccountWithPassword($username, $password, $roleIdentifiers, 'Typo3BackendProvider');
		$user->addAccount($account);

		return $user;
	}

}
?>