<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Admin::Controller;

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
class Setup extends F3::FLOW3::MVC::Controller::ActionController {

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
		$this->arguments->addNewArgument('indexlocation');
	}

	/**
	 * Processes a request.
	 *
	 * @param  F3::FLOW3::MVC::Request $request The request to process
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function defaultAction() {
		return '<html><head><title>TYPO3CR setup</title></head><body>
	<form action="setup">
		<table>
		<tr><th>DSN:</th><td><input type="text" name="dsn" size="30" /> (e.g. sqlite:/path/to/typo3cr.db)</td></tr>
		<tr><th>Username (optional):</th><td><input type="text" name="userid" size="30" /></td></tr>
		<tr><th>Password (optional):</th><td><input type="passsword" name="password" size="30" /></td></tr>
		<tr><th>Index location:</th><td><input type="text" name="indexlocation" size="30" /> (e.g. /path/to/index)</td></tr>
		</table>
		<input type="submit" value="OK" />
	</form>
</body></html>';
	}

	/**
	 * Initializes the database and index
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setupAction() {
		$helper = $this->componentFactory->getComponent('F3::TYPO3CR::Storage::Helper', $this->arguments);
		$helper->initialize();
		return 'Initialization of the TYPO3CR was successful.';
	}

}

?>