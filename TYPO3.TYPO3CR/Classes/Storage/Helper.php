<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A helper class for the storage layer
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Helper {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var \PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string
	 */
	protected $PDODriver;

	/**
	 * @param array $options
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($options) {
		$this->options = $options;
		$splitdsn = explode(':', $this->options['dsn'], 2);
		$this->PDODriver = $splitdsn[0];
	}

	/**
	 * Performs all-in-one setup of the TYPO3CR storage layer
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize() {
		$this->initializeStorage();
	}

	/**
	 * Sets up tables, nodetypes and root node
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function initializeStorage() {
		$this->databaseHandle = new \PDO($this->options['dsn'], $this->options['userid'], $this->options['password']);
		$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		if ($this->PDODriver == 'mysql') {
			$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI_QUOTES\';');
		}

		$this->initializeTables();
		$this->initializeNamespaces();
		$this->initializeNodeTypes();
		$this->initializeNodes();
	}

	/**
	 * Creates the tables needed
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeTables() {
		$lines = file(__DIR__ . '/../../Resources/SQL/TYPO3CR_schema.sql', FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
		$statement = '';
		foreach ($lines as $line) {
			$line = trim($line);
			if ($this->PDODriver != 'mysql') {
					// Remove MySQL style key length delimiters if we are not setting up a mysql db
				$line = preg_replace('/"\([0-9]+\)/', '"', $line);
			}

			$statement .= ' ' . $line;
			if (substr($statement, -1) == ';') {
				$this->databaseHandle->query($statement);
				$statement = '';
			}
		}
	}

	/**
	 * Clears the namespaces table and adds builtin namespaces to the database
	 *
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function initializeNamespaces() {
		$this->databaseHandle->query('DELETE FROM "namespaces"');
		$this->databaseHandle->query('INSERT INTO "namespaces" ("prefix", "uri") VALUES (\'jcr\', \'http://www.jcp.org/jcr/1.0\')');
		$this->databaseHandle->query('INSERT INTO "namespaces" ("prefix", "uri") VALUES (\'nt\', \'http://www.jcp.org/jcr/nt/1.0\')');
		$this->databaseHandle->query('INSERT INTO "namespaces" ("prefix", "uri") VALUES (\'mix\', \'http://www.jcp.org/jcr/mix/1.0\')');
		$this->databaseHandle->query('INSERT INTO "namespaces" ("prefix", "uri") VALUES (\'xml\', \'http://www.w3.org/XML/1998/namespace\')');
		$this->databaseHandle->query('INSERT INTO "namespaces" ("prefix", "uri") VALUES (\'flow3\', \'http://forge.typo3.org/namespaces/flow3\')');
	}

	/**
	 * Clears nodetypes and adds builtin nodetypes to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function initializeNodeTypes() {
		$this->databaseHandle->query('DELETE FROM "nodetypes"');
		$this->databaseHandle->query('INSERT INTO "nodetypes" ("name","namespace") VALUES (\'base\',\'http://www.jcp.org/jcr/nt/1.0\')');
		$this->databaseHandle->query('INSERT INTO "nodetypes" ("name","namespace") VALUES (\'unstructured\',\'http://www.jcp.org/jcr/nt/1.0\')');
	}

	/**
	 * Clears the nodes table and adds a root node to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function initializeNodes() {
		$this->databaseHandle->query('DELETE FROM "nodes"');
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodes" ("identifier", "name", "namespace", "parent", "nodetype", "nodetypenamespace") VALUES (?, \'\', \'\', \'\', \'unstructured\',\'http://www.jcp.org/jcr/nt/1.0\')');
		$statementHandle->execute(array(
			\F3\FLOW3\Utility\Algorithms::generateUUID()
		));
	}

}

?>