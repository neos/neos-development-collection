<?php
namespace TYPO3\TYPO3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The TYPO3 Setup
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class SetupCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * Create users with the Administrator role.
	 *
	 * @param string $identifier Identifier (username) of the account to be created
	 * @param string $password Password of the account to be created
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function createAdministratorCommand($identifier, $password) {
		$user = new \TYPO3\TYPO3\Domain\Model\User();
		$user->getPreferences()->set('context.workspace', 'user-' . $identifier);

		$account = $this->accountFactory->createAccountWithPassword($identifier, $password, array('Administrator'));
		$account->setParty($user);
		$this->accountRepository->add($account);
		$this->response->appendContent('Created account "' . $identifier . '".');
	}

}
?>