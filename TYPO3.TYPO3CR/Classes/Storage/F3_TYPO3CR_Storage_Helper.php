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
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 */

/**
 * A helper class for the storage layer
 *
 * @package TYPO3CR
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Helper {

	/**
	 * @var PDO
	 */
	protected $databaseHandle;

	/**
	 * Connects to the database using the provided DSN and (optional) user data
	 *
	 * @param string $dsn The DSN to use for connecting to the DB
	 * @param string $username The username to use for connecting to the DB
	 * @param string $password The password to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($dsn, $username = NULL, $password = NULL) {
		$this->databaseHandle = new PDO($dsn, $username, $password);
		$this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function initializeDatabase() {
		$this->initializeTables();
		$this->initializeRootNode();
		$this->initializeNodeTypes();
	}

	public function initializeTables() {
		$statements = file(FLOW3_PATH_PACKAGES . 'TYPO3CR/Resources/SQL/TYPO3CR.sql', FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
		foreach ($statements as $statement) {
			$this->databaseHandle->query($statement);
		}
	}

	/**
	 * Adds builtin nodetypes to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeNodeTypes() {
		$this->databaseHandle->query('INSERT INTO "nodetypes" ("name") VALUES (\'nt:base\')');
		$this->databaseHandle->query('INSERT INTO "nodetypes" ("name") VALUES (\'nt:unstructured\')');
	}

	/**
	 * Adds a root node to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeRootNode() {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodes" ("identifier", "name", "parent", "nodetype") VALUES (?, \'\', \'\', \'nt:unstructured\')');
		$statementHandle->execute(array(
			F3_FLOW3_Utility_Algorithms::generateUUID()
		));
	}

}

?>