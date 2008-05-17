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
 * @version $Id$
 */

/**
 * Tests for the StorageAccess implementation of TYPO3CR. Needs to be extended
 * for various storage types
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_StorageAccess_TestBase extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_TYPO3CR_StorageAccessInterface
	 */
	protected $storageAccess;

	/**
	 * Checks if we can store and remove a raw node properly.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @test
	 */
	public function addNodeAndRemoveNodeWork() {
		$UUID = $this->componentManager->getComponent('F3_FLOW3_Utility_Algorithms')->generateUUID();
		$expectedRawNode = array(
			'id' => NULL,
			'pid' => '96bca35d-1ef5-4a47-8b0c-0bfc69507d00',
			'name' => 'TestNode1',
			'uuid' => $UUID,
			'nodetype' => '1'
		);
		$this->storageAccess->addNode($UUID, '96bca35d-1ef5-4a47-8b0c-0bfc69507d00', '1', 'TestNode1');
		$rawNode = $this->storageAccess->getRawNodeByUUID($UUID);
		$this->assertTrue(is_array($rawNode), 'getRawNodeByUUID() did not return an array for a just created node entry.');
		$expectedRawNode['id'] = $rawNode['id'];
		$this->assertSame($expectedRawNode, $rawNode, 'The returned raw node had not the expected values.');

		$this->storageAccess->removeNode($UUID);
		$rawNode = $this->storageAccess->getRawNodeByUUID($UUID);
		$this->assertFalse($rawNode, 'getRawNodeByUUID() did return an array for a just removed node entry.');
	}

	/**
	 * Checks if we can update and remove a raw node properly
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @test
	 */
	public function updateNodeAndRemoveNodeWork() {
		$UUID = $this->componentManager->getComponent('F3_FLOW3_Utility_Algorithms')->generateUUID();
		$expectedRawNode = array(
			'id' => NULL,
			'pid' => '96bca35d-1ef5-4a47-8b0c-0bfc69507d00',
			'name' => 'TestNode2',
			'uuid' => $UUID,
			'nodetype' => '1'
		);
		$this->storageAccess->addNode($UUID, '96bca35d-1ef5-4a47-8b0c-0bfc69507d00', '1', 'TestNode2');
		$rawNode = $this->storageAccess->getRawNodeByUUID($UUID);
		$this->assertTrue(is_array($rawNode), 'getRawNodeByUUID() did not return an array for a just created node entry.');
		$expectedRawNode['id'] = $rawNode['id'];
		$this->assertSame($expectedRawNode, $rawNode, 'The returned raw node had not the expected values.');

		$expectedRawNodeUpdated = array(
			'id' => $rawNode['id'],
			'pid' => '96bca35d-1ef5-4a47-8b0c-0bfc69507d01',
			'name' => 'TestNode2Updated',
			'uuid' => $UUID,
			'nodetype' => '3'
		);
		$this->storageAccess->updateNode($UUID, '96bca35d-1ef5-4a47-8b0c-0bfc69507d01', '3', 'TestNode2Updated');
		$rawNodeUpdated = $this->storageAccess->getRawNodeByUUID($UUID);
		$this->assertTrue(is_array($rawNodeUpdated), 'getRawNodeByUUID() did not return an array for an updated node entry.');
		$this->assertSame($expectedRawNodeUpdated, $rawNodeUpdated, 'The returned raw node had not the expected (updated) values.');

		$this->storageAccess->removeNode($UUID);
		$rawNode = $this->storageAccess->getRawNodeByUUID($UUID);
		$this->assertFalse($rawNode, 'getRawNodeByUUID() did return an array for a just removed node entry.');
	}

}
?>