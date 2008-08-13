<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Controller for the TYPO3CR setup
 *
 * @package		TYPO3CR
 * @version 	$Id$
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Admin_Controller_Setup extends F3_FLOW3_MVC_Controller_ActionController {

	/**
	 * Initializes this controller
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeController() {
		$this->arguments->addNewArgument('dsn');
		$this->arguments->addNewArgument('userid');
		$this->arguments->addNewArgument('password');
	}

	/**
	 * Processes a CLI request.
	 *
	 * @param  F3_FLOW3_MVC_CLI_Request $request The request to process
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function defaultAction() {
		return $this->helpAction();
	}

	/**
	 * Initializes the database with some data
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function databaseAction() {
		if ($this->arguments['dsn'] == '') {
			return $this->helpAction();
		} else {
			$helper = $this->componentFactory->getComponent('F3_TYPO3CR_Storage_Helper', $this->arguments['dsn'], $this->arguments['userid'], $this->arguments['password']);
			$helper->initializeDatabase();
			return 'Database was initialized.' . chr(10);
		}
	}

	/**
	 * Displays a help screen
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function helpAction() {
		if ($this->request instanceof F3_FLOW3_MVC_CLI_Request) {
			return chr(10) .
				'TYPO3CR Setup' . chr(10) .
				'Usage: php index_dev.php TYPO3CR Setup database --dsn=DSN [--userid=USERID] [--password=PASSWORD]' . chr(10);
		} else {
			return chr(10) .
				'TYPO3CR Setup<br />' .
				'Usage: .../typo3cr/setup/database/?dsn=DSN[&amp;userid=USERID][&amp;password=PASSWORD]';
		}
	}
}

?>