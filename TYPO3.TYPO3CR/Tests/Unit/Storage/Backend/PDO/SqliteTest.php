<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage\Backend\PDO;

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

require_once(__DIR__ . '/../TestBase.php');

/**
 * Tests for the Storage_Backend_PDO implementation of TYPO3CR using the Sqlite PDO driver
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SqliteTest extends \F3\TYPO3CR\Storage\Backend\TestBase {

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
		$environment = $this->objectManager->getObject('F3\FLOW3\Utility\Environment');
		$this->fixtureFolder = $environment->getPathToTemporaryDirectory() . 'TYPO3CR/Tests/';
		\F3\FLOW3\Utility\Files::createDirectoryRecursively($this->fixtureFolder);
		$this->fixtureDB = uniqid('sqlite') . '.db';
		copy(__DIR__ . '/../../../Fixtures/TYPO3CR.db', $this->fixtureFolder . $this->fixtureDB);
		$this->storageBackend = new \F3\TYPO3CR\Storage\Backend\PDO(array('dataSourceName' => 'sqlite:' . $this->fixtureFolder . $this->fixtureDB));
		$this->storageBackend->setSearchBackend($this->getMock('F3\TYPO3CR\Storage\SearchInterface'));
		$this->storageBackend->connect();

		parent::setup();
	}

	/**
	 * Clean up after the tests
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function tearDown() {
		if ($this->storageBackend instanceof \F3\TYPO3CR\Storage\Backend\PDO) {
			$this->storageBackend->disconnect();
		}
		unlink($this->fixtureFolder . $this->fixtureDB);
		\F3\FLOW3\Utility\Files::removeDirectoryRecursively($this->fixtureFolder);
	}
}
?>