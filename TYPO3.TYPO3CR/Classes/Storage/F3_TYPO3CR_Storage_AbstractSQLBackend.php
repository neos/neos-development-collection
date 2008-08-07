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
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id$
 */

/**
 * An abstract storage backend for SQL RRDBMS
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_TYPO3CR_Storage_AbstractSQLBackend extends F3_TYPO3CR_Storage_AbstractBackend {

	/**
	 * @var string
	 */
	protected $dataSourceName;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * Sets the DSN to use
	 *
	 * @param string $DSN The DSN to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDataSourceName($DSN) {
		$this->dataSourceName = $DSN;
	}

	/**
	 * Sets the username to use
	 *
	 * @param string $username The username to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Sets the password to use
	 *
	 * @param $password The password to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

}
?>