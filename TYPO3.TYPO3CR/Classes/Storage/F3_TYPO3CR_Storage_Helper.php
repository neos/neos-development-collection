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

require_once('Zend/Search/Lucene.php');

/**
 * A helper class for the storage layer
 *
 * @package TYPO3CR
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Helper {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var PDO
	 */
	protected $databaseHandle;

	/**
	 * @param array $options
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($options) {
		$this->options = $options;
	}

	/**
	 * Performs all-in-one setup of the TYPO3CR storage layer
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize() {
		$this->initializeStorage();
		$this->initializeSearch();
	}

	/**
	 * Sets up tables, nodetypes and root node
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeStorage() {
		$this->databaseHandle = new PDO($this->options['dsn'], $this->options['userid'], $this->options['password']);
		$this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->initializeTables();
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
		$statements = file(FLOW3_PATH_PACKAGES . 'TYPO3CR/Resources/SQL/TYPO3CR.sql', FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
		foreach ($statements as $statement) {
			$this->databaseHandle->query($statement);
		}
	}

	/**
	 * Clears nodetypes and adds builtin nodetypes to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeNodeTypes() {
		$this->databaseHandle->query('DELETE FROM "nodetypes"');
		$this->databaseHandle->query('INSERT INTO "nodetypes" ("name") VALUES (\'nt:base\')');
		$this->databaseHandle->query('INSERT INTO "nodetypes" ("name") VALUES (\'nt:unstructured\')');
	}

	/**
	 * Clears the nodes table and adds a root node to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeNodes() {
		$this->databaseHandle->query('DELETE FROM "nodes"');
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodes" ("identifier", "name", "parent", "nodetype") VALUES (?, \'\', \'\', \'nt:unstructured\')');
		$statementHandle->execute(array(
			F3_FLOW3_Utility_Algorithms::generateUUID()
		));
	}

	/**
	 * Sets up the search backend
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeSearch() {
		$index = Zend_Search_Lucene::create($this->options['indexlocation']. '/default');
		$this->populateIndex();
	}

	/**
	 * Adds the root node to the index
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function populateIndex() {
		$statementHandle = $this->databaseHandle->query('SELECT * FROM "nodes" WHERE "parent" = \'\'');
		$node = $statementHandle->fetch(PDO::FETCH_ASSOC);

		$nodeDocument = new Zend_Search_Lucene_Document();
		$nodeDocument->addField(Zend_Search_Lucene_Field::Keyword('identifier', $node['identifier']));
		$nodeDocument->addField(Zend_Search_Lucene_Field::Keyword('nodetype', $node['nodetype']));
		$nodeDocument->addField(Zend_Search_Lucene_Field::Keyword('path', '/'));

		$index = Zend_Search_Lucene::open($this->options['indexlocation']. '/default');
		$index->addDocument($nodeDocument);
	}
}

?>