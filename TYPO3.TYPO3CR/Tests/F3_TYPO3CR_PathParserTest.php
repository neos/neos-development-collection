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
 * Tests for the PathParser implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_PathParserTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_TYPO3CR_Node
	 */
	protected $rootNode;

	/**
	 * @var F3_TYPO3CR_PathParser
	 */
	protected $pathParser;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->pathParser = new F3_TYPO3CR_PathParser();
	}

	/**
	 * Checks if we receive the root node properly
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function weGetTheRootNode() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$mockStorageAccess->expects($this->any())->method('getUUIDsOfSubNodesOfNode')->will($this->returnValue(array()));
		$mockStorageAccess->expects($this->any())->method('getRawNodeTypeById')->will($this->returnValue(array('id' => 1, 'name' => 'nodeTypeName')));
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = new F3_TYPO3CR_Session('workspaceName', $mockRepository, $mockStorageAccess, $this->componentManager);

		$rootNode = new F3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager);
		$rootNode->initializeFromArray(array(
			'id' => '1',
			'uuid' => '',
			'pid' => '0',
			'nodetype' => '1',
			'name' => 'nodeA'
		));

		$firstNode = $this->pathParser->parsePath('/', $rootNode);
		$this->assertEquals($rootNode, $firstNode, 'The path parser did not return the root node.');

		$secondNode = $this->pathParser->parsePath('/./', $rootNode);
		$this->assertEquals($rootNode, $secondNode, 'The path parser did not return the root node.');
	}

	/**
	 * Checks if we receive a sub node property properly
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function propertiesAreRetrievedCorrectly() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'pid' => 0,
				'nodetype' => 0,
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'pid' => 0,
					'nodetype' => 0,
					'name' => ''
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d10' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'pid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 0,
					'name' => 'Content'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
					'pid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 0,
					'name' => 'News'
				),
			)
		);
		$mockStorageAccess->rawPropertiesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					array(
						'name' => 'title',
						'value' => 'News about the TYPO3CR',
						'namespace' => '',
						'multivalue' => FALSE
					)
				)
			)
		);

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$rootNode = $session->getRootNode();

		$expectedTitle = 'News about the TYPO3CR';
		$newsItem = $this->pathParser->parsePath('Content/News/title', $rootNode, F3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		$this->assertEquals($expectedTitle, $newsItem->getString(), 'The path parser did not return the expected property value.');
	}

	/**
	 * Checks if we receive the same sub node property twice
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function propertyObjectsAreIdentical() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'pid' => 0,
				'nodetype' => 0,
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'pid' => 0,
					'nodetype' => 0,
					'name' => ''
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
					'pid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 0,
					'name' => 'Node'
				),
			)
		);
		$mockStorageAccess->rawPropertiesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					array(
						'name' => 'title',
						'value' => 'Same title, same object!?',
						'namespace' => '',
						'multivalue' => FALSE
					)
				)
			)
		);

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$rootNode = $session->getRootNode();
		$property1 = $this->pathParser->parsePath('Node/title', $rootNode, F3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		$property2 = $this->pathParser->parsePath('Node/title', $rootNode, F3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		$this->assertSame($property1, $property2, 'The path parser did not return the same object.');
	}

	/**
	 * Checks if we receive a sub node properly
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function subnodesAreRetrievedProperly() {
		$expectedContentNodeUUID = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$expectedHomeNodeUUID = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';

		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'pid' => 0,
				'nodetype' => 0,
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'pid' => 0,
					'nodetype' => 0,
					'name' => ''
				),
				$expectedContentNodeUUID => array(
					'uuid' => $expectedContentNodeUUID,
					'pid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 0,
					'name' => 'Content'
				),
				$expectedHomeNodeUUID => array(
					'uuid' => $expectedHomeNodeUUID,
					'pid' => $expectedContentNodeUUID,
					'nodetype' => 0,
					'name' => 'Home'
				),
			)
		);

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$rootNode = $session->getRootNode();

		$node = $this->pathParser->parsePath('/Content', $rootNode);
		$this->assertEquals($expectedContentNodeUUID, $node->getUUID(), 'The path parser did not return the correct content node.');

		$node = $this->pathParser->parsePath('/Content/', $rootNode);
		$this->assertEquals($expectedContentNodeUUID, $node->getUUID(), 'The path parser did not return the correct content node.');

		$node = $this->pathParser->parsePath('/Content/.', $rootNode);
		$this->assertEquals($expectedContentNodeUUID, $node->getUUID(), 'The path parser did not return the correct content node.');

		$node = $this->pathParser->parsePath('Content/..', $rootNode);
		$this->assertEquals($rootNode->getUUID(), $node->getUUID(), 'The path parser did not return the correct root node.');

		$node = $this->pathParser->parsePath('Content/./Home', $rootNode);
		$this->assertEquals($expectedHomeNodeUUID, $node->getUUID(), 'The path parser did not return the home page.');
	}

	/**
	 * Test for index based notation
	 * @test
	 */
	public function indexBasedNotationWorks() {
		throw new PHPUnit_Framework_IncompleteTestError('Test for same-name siblings not implemented yet.', 1211051286);
	}

	/**
	 * Test for fetching properties
	 * @test
	 */
	public function fetchingPropertiesWorks() {
		throw new PHPUnit_Framework_IncompleteTestError('Test for property fetching not implemented yet.', 1211051287);
	}

}
?>