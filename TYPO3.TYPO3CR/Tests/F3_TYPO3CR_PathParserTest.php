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
	 * Checks if we receive the root node properly
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function weGetTheRootNode() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$mockStorageAccess->expects($this->any())->method('getIdentifiersOfSubNodesOfNode')->will($this->returnValue(array()));
		$mockStorageAccess->expects($this->any())->method('getRawNodeType')->will($this->returnValue(array('name' => 'nodeTypeName')));
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = new F3_TYPO3CR_Session('workspaceName', $mockRepository, $mockStorageAccess, $this->componentManager);

		$rawData = array(
			'identifier' => '',
			'parent' => 0,
			'nodetype' => 'nodeTypeName',
			'name' => 'nodeA'
		);
		$rootNode = new F3_TYPO3CR_Node($rawData, $mockSession, $mockStorageAccess, $this->componentManager);

		$firstNode = F3_TYPO3CR_PathParser::parsePath('/', $rootNode);
		$this->assertEquals($rootNode, $firstNode, 'The path parser did not return the root node.');

		$secondNode = F3_TYPO3CR_PathParser::parsePath('/./', $rootNode);
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
				'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'parent' => 0,
				'nodetype' => 'nt:base',
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByIdentifierGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'parent' => 0,
					'nodetype' => 'nt:base',
					'name' => ''
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d10' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 'nt:base',
					'name' => 'Content'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'News'
				),
			)
		);
		$mockStorageAccess->rawPropertiesByIdentifierGroupedByWorkspace = array(
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
		$newsItem = F3_TYPO3CR_PathParser::parsePath('Content/News/title', $rootNode, F3_TYPO3CR_PathParser::SEARCH_MODE_PROPERTIES);
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
				'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'parent' => 0,
				'nodetype' => 'nt:base',
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByIdentifierGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'parent' => 0,
					'nodetype' => 'nt:base',
					'name' => ''
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 'nt:base',
					'name' => 'Node'
				),
			)
		);
		$mockStorageAccess->rawPropertiesByIdentifierGroupedByWorkspace = array(
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
		$property1 = F3_TYPO3CR_PathParser::parsePath('Node/title', $rootNode, F3_TYPO3CR_PathParser::SEARCH_MODE_PROPERTIES);
		$property2 = F3_TYPO3CR_PathParser::parsePath('Node/title', $rootNode, F3_TYPO3CR_PathParser::SEARCH_MODE_PROPERTIES);
		$this->assertSame($property1, $property2, 'The path parser did not return the same object.');
	}

	/**
	 * Checks if we receive a sub node properly
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function subnodesAreRetrievedProperly() {
		$expectedContentNodeIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$expectedHomeNodeIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';

		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'parent' => 0,
				'nodetype' => 'nt:base',
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByIdentifierGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'parent' => 0,
					'nodetype' => 'nt:base',
					'name' => ''
				),
				$expectedContentNodeIdentifier => array(
					'identifier' => $expectedContentNodeIdentifier,
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 'nt:base',
					'name' => 'Content'
				),
				$expectedHomeNodeIdentifier => array(
					'identifier' => $expectedHomeNodeIdentifier,
					'parent' => $expectedContentNodeIdentifier,
					'nodetype' => 'nt:base',
					'name' => 'Home'
				),
			)
		);

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$rootNode = $session->getRootNode();

		$node = F3_TYPO3CR_PathParser::parsePath('/Content', $rootNode);
		$this->assertEquals($expectedContentNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the correct content node.');

		$node = F3_TYPO3CR_PathParser::parsePath('/Content/', $rootNode);
		$this->assertEquals($expectedContentNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the correct content node.');

		$node = F3_TYPO3CR_PathParser::parsePath('/Content/.', $rootNode);
		$this->assertEquals($expectedContentNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the correct content node.');

		$node = F3_TYPO3CR_PathParser::parsePath('Content/..', $rootNode);
		$this->assertEquals($rootNode->getIdentifier(), $node->getIdentifier(), 'The path parser did not return the correct root node.');

		$node = F3_TYPO3CR_PathParser::parsePath('Content/./Home', $rootNode);
		$this->assertEquals($expectedHomeNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the home page.');
	}

}
?>