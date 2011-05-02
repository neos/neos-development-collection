<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Command;

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
class SetupCommandController extends \F3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * Create users with the Administrator role.
	 *
	 * Input is expected as TSV with the fields
	 * identifier, password
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createAdministratorCommand() {
			// no file_get_contents here because it does not work on php://stdin
		$fp = fopen('php://stdin', 'rb');
		while (!feof($fp)) {
			$input = fgets($fp, 4096);
			if (empty($input)) break;

			list($identifier, $password) = explode('	', $input);
			$identifier = trim($identifier);
			$password = trim($password);

			$user = new \F3\TYPO3\Domain\Model\User();
			$user->getPreferences()->set('context.workspace', 'user-' . $identifier);

			$account = $this->accountFactory->createAccountWithPassword($identifier, $password, array('Administrator'));
			$account->setParty($user);
			$this->accountRepository->add($account);
		}
		fclose($fp);
	}

}
?>