<?php
declare(encoding = 'utf-8');

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

require_once('TYPO3CR_BaseTest.php');

/**
 * Tests for the NodeType implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @author 		Karsten Dambekalns <karsten@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_NodeTypeTest extends TYPO3CR_BaseTest {

	/**
	 * @var T3_TYPO3CR_Node
	 */
	protected $rootNode;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->rootNode = $this->session->getRootNode();
	}

	/**
	 * Checks if the NodeType object returned by two different nodes is not the same.
	 * @test
	 */
	public function nodeTypeObjectReturnedIsDifferent() {
		$uuid = '96b4a35d-1ef5-4a47-8b0c-0bfc69507d03';
		$node = $this->session->getNodeByUUID($uuid);

		$this->assertNotEquals($this->rootNode->getPrimaryNodeType(), $node->getPrimaryNodeType(), 'getPrimaryNodeType() did not return a different NodeType object on two different nodes.');
	}

	/**
	 * Checks if getName() returns the expected name of a NodeType object
	 * @test
	 */
	public function getNameReturnsTheExpectedName() {
		$uuid = '96b4a35d-1ef5-4a47-8b0c-0bfc69507d03';
		$expectedNodeTypeName = 'Category';

		$node = $this->session->getNodeByUUID($uuid);
		$this->assertEquals($uuid, $node->getUUID(), 'getUUID() did not return the expected UUID.');

		$nodeTypeName = $node->getPrimaryNodeType()->getName();
		$this->assertEquals($expectedNodeTypeName, $nodeTypeName, 'getName() on the NodeType did not return the expected name: '.$expectedNodeTypeName.' != '.$nodeTypeName);
	}
}
?>
