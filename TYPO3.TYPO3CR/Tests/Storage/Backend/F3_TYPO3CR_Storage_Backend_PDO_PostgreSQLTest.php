<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Storage::Backend::PDO;

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
 * @version $Id:F3::TYPO3CR::Storage::Backend::PDOTest.php 888 2008-05-30 16:00:05Z k-fish $
 */

require_once('F3_TYPO3CR_Storage_Backend_TestBase.php');

/**
 * Tests for the Storage_Backend_PDO implementation of TYPO3CR using the PDO PostgreSQL driver
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id:F3::TYPO3CR::Storage::Backend::PDOTest.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PostgreSQLTest extends F3::TYPO3CR::Storage::Backend::TestBase {

	/**
	 * @var string
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $dbuser;

	/**
	 * @var string
	 */
	protected $dbpass;

	/**
	 * Set up the test environment
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hörmann <hoermann@saltation.de>
	 */
	public function setUp() {
		$this->config = FLOW3_PATH_PACKAGES . 'TYPO3CR/Tests/Fixtures/testdb.conf';
		$lines = file($this->config, FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$line = trim($line);
			$prefix = 'PGSQL_DB="';
			if (strncmp($line, $prefix, strlen($prefix)) == 0) {
				$this->db = substr($line, strlen($prefix), -1);
			}
			$prefix = 'PGSQL_USER="';
			if (strncmp($line, $prefix, strlen($prefix)) == 0) {
				$this->dbuser = substr($line, strlen($prefix), -1);
			}
			$prefix = 'PGSQL_PASS="';
			if (strncmp($line, $prefix, strlen($prefix)) == 0) {
				$this->dbpass = substr($line, strlen($prefix), -1);
			}
		}

		if ($this->db != '' && $this->dbuser != '' && $this->dbpass != '') {
			try {
				$databaseHandle = new PDO('pgsql:dbname=' . $this->db, $this->dbuser, $this->dbpass);
				$databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$databaseHandle = NULL;
			} catch (PDOException $e) {
				$this->markTestSkipped('Could not connect to PostgreSQL database ' . $this->db . ', user ' . $this->dbuser . ', password ' . $this->dbpass . ', skipping PostgreSQL tests');
				return;
			}
		} else {
			$this->markTestSkipped('PostgreSQL tests not configured');
		}

		$scriptpath = FLOW3_PATH_PACKAGES . 'TYPO3CR/Tests/Fixtures/';

		exec($scriptpath . 'testdb.sh postgres reset');

		$this->storageBackend = new F3::TYPO3CR::Storage::Backend::PDO(array('dataSourceName' => 'pgsql:dbname=' . $this->db, 'username' => $this->dbuser, 'password' => $this->dbpass));
		$this->storageBackend->setSearchEngine($this->getMock('F3::TYPO3CR::Storage::SearchInterface'));
		$this->storageBackend->connect();

		parent::setup();
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
	}
}
?>