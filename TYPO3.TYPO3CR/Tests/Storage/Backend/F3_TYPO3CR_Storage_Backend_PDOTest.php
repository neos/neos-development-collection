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
 * Tests for the Storage_Backend_PDO implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDOTest.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Backend_PDOTest extends F3_TYPO3CR_Storage_Backend_TestBase {

	/**
	 * @var string
	 */
	protected $fixtureFolder;

	/**
	 * @var string
	 */
	protected $fixtureDB;

	/**
	 * Set up the test environment
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$environment = $this->componentFactory->getComponent('F3_FLOW3_Utility_Environment');
		$this->fixtureFolder = $environment->getPathToTemporaryDirectory() . 'TYPO3CR/Tests/';
		F3_FLOW3_Utility_Files::createDirectoryRecursively($this->fixtureFolder);
		$this->fixtureDB = uniqid('sqlite') . '.db';
		copy(FLOW3_PATH_PACKAGES . 'TYPO3CR/Tests/Fixtures/TYPO3CR.db', $this->fixtureFolder . $this->fixtureDB);
		$this->storageAccess = new F3_TYPO3CR_Storage_Backend_PDO(array('dataSourceName' => 'sqlite:' . $this->fixtureFolder . $this->fixtureDB));
		$this->storageAccess->connect();
	}

	/**
	 * Clean up after the tests
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function tearDown() {
		unlink($this->fixtureFolder . $this->fixtureDB);
		F3_FLOW3_Utility_Files::removeDirectoryRecursively($this->fixtureFolder);
	}
}
?>