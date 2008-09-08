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
 * @subpackage Tests
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDOTest.php 888 2008-05-30 16:00:05Z k-fish $
 */

require_once('F3_TYPO3CR_Storage_Backend_TestBase.php');

/**
 * Tests for the Storage_Backend_PDO implementation of TYPO3CR using the PDO PostgreSQL driver
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDOTest.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Backend_PDO_PostgreSQLTest extends F3_TYPO3CR_Storage_Backend_TestBase {

	/**
	 * @var string
	 */
	protected $fixtureDB;

	/**
	 * @var string
	 */
	protected $dbuser = 'typo3v5testing';

	/**
	 * @var string
	 */
	protected $dbpass = 'typo3v5testingpass';

	/**
	 * @var string
	 */
	protected $templatedb = 'typo3v5testbase';

	/**
	 * Set up the test environment
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hörmann <hoermann@saltation.de>
	 */
	public function setUp() {
		$templatedsn = 'pgsql:dbname=' . $this->templatedb;

		$this->fixtureDB = 'typo3v5test' . uniqid('postgresql');

		try {
			$databaseHandle = new PDO($templatedsn, $this->dbuser, $this->dbpass);
			$databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			$this->markTestSkipped('Could not connect to PostgreSQL template database, skipping PostgreSQL tests (DSN "' . $templatedsn . '"). PDO error: ' . $e->getMessage());
		}

		$databaseHandle->exec('CREATE DATABASE ' . $this->fixtureDB . ' TEMPLATE ' . $this->templatedb . ';');

		$databaseHandle = NULL;

		$this->storageBackend = new F3_TYPO3CR_Storage_Backend_PDO(array('dataSourceName' => 'pgsql:dbname=' . $this->fixtureDB, 'username' => $this->dbuser, 'password' => $this->dbpass));
		$this->storageBackend->setSearchEngine($this->getMock('F3_TYPO3CR_Storage_SearchInterface'));
		$this->storageBackend->connect();
	}

	/**
	 * Clean up after the tests
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hörmann <hoermann@saltation.de>
	 */
	public function tearDown() {
		$this->storageBackend->disconnect();

		$templatedsn = 'pgsql:dbname=' . $this->templatedb;

		try {
			$databaseHandle = new PDO($templatedsn, $this->dbuser, $this->dbpass);
			$databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new F3_TYPO3CR_StorageException('Could not connect to DSN "' . $templatedsn . '". PDO error: ' . $e->getMessage(), 1220609558);
		}

		$databaseHandle->exec('DROP DATABASE ' . $this->fixtureDB . ';');

		$databaseHandle = NULL;
	}
}
?>