<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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

require_once('Fixtures/MockStorageBackend.php');

/**
 * Tests for the Node implementation of TYPO3CR
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3CR\MockStorageBackend
	 */
	protected $mockStorageBackend;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$this->mockStorageBackend = new \F3\TYPO3CR\MockStorageBackend();
		$this->mockStorageBackend->rawRootNodesByWorkspace = array(
			'default' => array(
				'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'parent' => 0,
				'nodetype' => 'nt:base',
				'name' => ''
			)
		);
		$this->mockStorageBackend->rawNodesByIdentifierGroupedByWorkspace = array(
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
					'nodetype' => 0,
					'name' => 'Content'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'News'
				),
				'96bca35d-1ef5-4a47-8c0c-6ddd68507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8c0c-6ddd68507d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'ExternalRefParent'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d07' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d07',
					'parent' => '96bca35d-1ef5-4a47-8c0c-6ddd68507d00',
					'nodetype' => 'nt:base',
					'name' => 'WrongRefSource'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d15' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d15',
					'parent' => '96bca35d-1ef5-4a47-8c0c-6ddd68507d00',
					'nodetype' => 'nt:base',
					'name' => 'RefSource'
				),
				'96bca35d-1df5-4a47-8c0c-6dde68607d00' => array(
					'identifier' => '96bca35d-1df5-4a47-8c0c-6dde68607d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'InternalRefParent'
				),
				'96b6a351-1e35-4a47-8b0c-0d0d68507d07' => array(
					'identifier' => '96b6a351-1e35-4a47-8b0c-0d0d68507d07',
					'parent' => '96bca35d-1df5-4a47-8c0c-6dde68607d00',
					'nodetype' => 'nt:base',
					'name' => 'RefTarget'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69567d15' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69567d15',
					'parent' => '96bca35d-1df5-4a47-8c0c-6dde68607d00',
					'nodetype' => 'nt:base',
					'name' => 'RefSource'
				)
			)
		);
		$this->mockStorageBackend->rawPropertiesByIdentifierGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					array(
						'name' => 'title',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
						'value' => 'News about the TYPO3CR',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::STRING
					)
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d15' => array(
					array(
						'name' => 'ref',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d15',
						'value' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::REFERENCE
					),
					array(
						'name' => 'weakref',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d15',
						'value' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::WEAKREFERENCE
					)
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d07' => array(
					array(
						'name' => 'wrongweakref',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d07',
						'value' => '96bcd35d-2ef5-4a57-0b0c-0d3d69507d00',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::REFERENCE
					)
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69567d15' => array(
					array(
						'name' => 'ref',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69567d15',
						'value' => '96b6a351-1e35-4a47-8b0c-0d0d68507d07',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::REFERENCE
					)
				)
			)
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function newNodeIsRegisteredAsNew() {
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$session->expects($this->once())->method('registerNodeAsNew');

		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'User',
			'nodetype' => 'nt:base'
		);
		$this->getMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function newNodeIsNotRegisteredAsDirty() {
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$session->expects($this->never())->method('registerNodeAsDirty');

		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'User',
			'nodetype' => 'nt:base'
		);
		$this->getMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));
	}

	/**
	 * Checks if getReferences returns nothing when called on a node that is not referenced
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getReferencesReturnsNothingOnUnReferencedNode() {
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$session->expects($this->once())->method('getStorageBackend')->will($this->returnValue($this->mockStorageBackend));

		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\PropertyIteratorInterface', array());
		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'User',
			'nodetype' => 'nt:base'
		);
		$node = $this->getMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));
		$node->getReferences();
	}

	/**
	 * Checks if getWeakReferences returns nothing when called on a node that is not referenced
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getWeakReferencesReturnsNothingOnUnReferencedNode() {
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$session->expects($this->once())->method('getStorageBackend')->will($this->returnValue($this->mockStorageBackend));

		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\PropertyIteratorInterface', array());
		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'User',
			'nodetype' => 'nt:base'
		);
		$node = $this->getMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));
		$node->getWeakReferences();
	}

	/**
	 * Checks if hasProperty() works with various paths
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasPropertyWorks() {
		$this->mockObjectManager->expects($this->any())->method('create')->with('F3\PHPCR\NodeIteratorInterface', array())->will($this->returnValue(array()));

		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'News',
			'nodetype' => 'nt:base'
		);
		$newsNode = $this->getAccessibleMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes', 'getParent'), array($rawData, $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE), $this->mockObjectManager));
		$newsNode->_set('properties', array('title' => 'fake-title-property'));
		$newsNode->expects($this->any())->method('getParent')->will($this->returnValue($rootNode));
		$rootNode->expects($this->any())->method('getNodes')->will($this->returnValue(array($newsNode)));

		$this->assertTrue($newsNode->hasProperty('title'), 'Expected property was not found (1).');
		$this->assertTrue($newsNode->hasProperty('./title'), 'Expected property was not found (2).');
		$this->assertTrue($newsNode->hasProperty('../News/title'), 'Expected property was not found (3).');

		$this->assertFalse($newsNode->hasProperty('nonexistant'), 'Unxpected property was found (1).');
		$this->assertFalse($newsNode->hasProperty('./nonexistant'), 'Unexpected property wasfound (2).');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertiesReturnsPropertyIteratorWithProperties() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\PropertyIteratorInterface', array('properties'))->will($this->returnValue('would-be-iterator'));
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('properties', array('properties'));
		$node->_set('objectManager', $mockObjectManager);
		$this->assertEquals('would-be-iterator', $node->getProperties());
	}

	/**
	 * Checks if getProperty() works with various paths
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertyWorks() {
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'News',
			'nodetype' => 'nt:base'
		);
		$newsNode = $this->getAccessibleMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes', 'getParent'), array($rawData, $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE), $this->mockObjectManager));
		$newsNode->_set('properties', array('title' => 'fake-title-property'));
		$newsNode->expects($this->any())->method('getParent')->will($this->returnValue($rootNode));
		$rootNode->expects($this->any())->method('getNodes')->will($this->returnValue(array($newsNode)));

		$this->assertEquals($newsNode->getProperty('title'), 'fake-title-property');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPrimaryNodeTypeAsksForNodeType() {
		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('getNodeType')->with('nt:base');
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$session->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$rawData = array(
			'parent' => 'fakeUuid',
			'name' => 'News',
			'nodetype' => 'nt:base'
		);
		$node = $this->getMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));

		$node->getPrimaryNodeType();
	}

	/**
	 * Checks if hasNodes() works as it should.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNodesWorks() {
		$rawData = array(
			'parent' => 'myUuid',
			'name' => 'News',
			'nodetype' => 'nt:base'
		);
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE), $this->mockObjectManager));

		$this->assertFalse($node->hasNodes(), 'hasNodes() did not return FALSE for a node without child nodes.');

		$node->_set('nodes', array('fakeUuid'));
		$this->assertTrue($node->hasNodes(), 'hasNodes() did not return TRUE for a node with child nodes.');
	}

	/**
	 * Checks if getNodes() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodesWorks() {
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$session->expects($this->once())->method('getNodeByIdentifier')->with('fakeUuid')->will($this->returnValue('fakeNode'));
		$rawData = array(
			'parent' => 'myUuid',
			'name' => 'News',
			'nodetype' => 'nt:base'
		);
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));
		$node->_set('nodes', array('fakeUuid'));

		$node->getNodes();
	}

	/**
	 * Tests if getName() returns same as last name returned by getPath()
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNameReturnsNameFromDataGivenToConstructor() {
		$session = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$rawData = array(
			'parent' => 'myUuid',
			'name' => 'News',
			'nodetype' => 'nt:base'
		);
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('initializeProperties', 'initializeNodes'), array($rawData, $session, $this->mockObjectManager));
		$this->assertEquals('News', $node->getName());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getParentReturnsExistingNode() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('parentNode', $parentNode);
		$this->assertSame($parentNode, $node->getParent());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getParentReturnsInitializesNodeIfNeeded() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with('parentUuid')->will($this->returnValue($parentNode));
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);
		$node->_set('parentNode', 'parentUuid');
		$this->assertSame($parentNode, $node->getParent());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\PHPCR\ItemNotFoundException
	 */
	public function getParentOfRootFails() {
		$node = $this->getMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->getParent();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPathReturnsSlashForRootNode() {
		$node = $this->getMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$this->assertEquals('/', $node->getPath());
	}

	/**
	 * Tests if getPath() returns the correct path.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPathWithoutSameNameSiblingsWorks() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array('getPath', 'getParent'), array(), '', FALSE);
		$parentNode->expects($this->once())->method('getPath')->will($this->returnValue('/Content'));
		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('getParent'), array(), '', FALSE);
		$node->expects($this->once())->method('getParent')->will($this->returnValue($parentNode));
		$node->_set('name', 'News');
		$node->_set('parentNode', 'parentUuid');

		$this->assertEquals('/Content/News', $node->getPath());
	}

	/**
	 * Test if addNode() returns a Node.
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeReturnsANode() {
		$identifier = '16bca35d-1ef5-4a47-8b0c-0ddd69507d00';
		$expectedRawData = array(
			'parent' => NULL,
			'name' => 'new-node',
			'nodetype' => 'nt:base',
			'newidentifier' => $identifier
		);
		$mockNewNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNewNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($identifier));

		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeInterface', $expectedRawData, $mockSession)->will($this->returnValue($mockNewNode));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);
		$node->_set('objectManager', $mockObjectManager);
		$this->assertSame($mockNewNode, $node->addNode('new-node', 'nt:base', $identifier));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeSetsModifiedStatusOfNode() {
		$mockNewNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNewNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('uuid'));

		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('create')->will($this->returnValue($mockNewNode));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);
		$node->_set('objectManager', $mockObjectManager);
		$mockSession->expects($this->once())->method('registerNodeAsDirty')->with($node);

		$node->addNode('new-node', 'nt:base');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\ConstraintViolationException
	 */
	public function removeOnRootNodeThrowsException() {
			// the root node is the one with parent === NULL, so this one is one :)
		$node = $this->getMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->remove();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeCallsRemoveOnChildNodes() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);
		$subNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);
		$subNode->expects($this->once())->method('remove');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with('subnodeuuid')->will($this->returnValue($subNode));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('getParent'), array(), '', FALSE);
		$node->expects($this->any())->method('getParent')->will($this->returnValue($parentNode));
		$node->_set('nodes', array('subnodeuuid'));
		$node->_set('session', $mockSession);
		$node->_set('parentNode', $parentNode);
		$node->remove();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeCallsRemoveOnProperties() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);
		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->once())->method('remove');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('getParent'), array(), '', FALSE);
		$node->expects($this->any())->method('getParent')->will($this->returnValue($parentNode));
		$node->_set('properties', array($property));
		$node->_set('session', $mockSession);
		$node->_set('parentNode', $parentNode);
		$node->remove();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeRegistersNodeAsRemovedInSession() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('getParent'), array(), '', FALSE);
		$node->expects($this->any())->method('getParent')->will($this->returnValue($parentNode));
		$node->_set('session', $mockSession);
		$node->_set('parentNode', $parentNode);

		$mockSession->expects($this->once())->method('registerNodeAsRemoved')->with($node);

		$node->remove();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeCallsRemoveNodeOnParent() {
		$parentNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);
		$parentNode->expects($this->once())->method('removeNode')->with('nodeUuid');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('getParent'), array(), '', FALSE);
		$node->expects($this->once())->method('getParent')->will($this->returnValue($parentNode));
		$node->_set('session', $mockSession);
		$node->_set('identifier', 'nodeUuid');
		$node->_set('parentNode', $parentNode);

		$node->remove();
	}

	/**
	 * @test
	 * @expectedException \F3\PHPCR\RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPropertyChecksNameWithIsValidName() {
		$node = $this->getMock('F3\TYPO3CR\Node', array('isValidName'), array(), '', FALSE);
		$node->expects($this->once())->method('isValidName')->with('invalidname')->will($this->returnValue(FALSE));
       	$node->setProperty('invalidname', 'nt:base');
	}

	/**
	 * Provides test data for setPropertySetsValue
	 *
	 * @return array of arrays with parameters for setPropertySetsValue()
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function convertibleProperties() {
		return array(
			array(\F3\PHPCR\PropertyType::UNDEFINED, 'someValue', array('someValue', \F3\PHPCR\PropertyType::STRING)),
			array(\F3\PHPCR\PropertyType::UNDEFINED, TRUE, array(TRUE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::UNDEFINED, FALSE, array(FALSE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::UNDEFINED, 12345, array(12345, \F3\PHPCR\PropertyType::LONG)),
			array(\F3\PHPCR\PropertyType::STRING, 'someValue', array('someValue', \F3\PHPCR\PropertyType::STRING)),
			array(\F3\PHPCR\PropertyType::STRING, 12345, array('12345', \F3\PHPCR\PropertyType::STRING)),
			array(\F3\PHPCR\PropertyType::STRING, 12345.6, array('12345.6', \F3\PHPCR\PropertyType::STRING)),
			array(\F3\PHPCR\PropertyType::STRING, TRUE, array('true', \F3\PHPCR\PropertyType::STRING)),
			array(\F3\PHPCR\PropertyType::STRING, FALSE, array('false', \F3\PHPCR\PropertyType::STRING)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, TRUE, array(TRUE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, FALSE, array(FALSE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::LONG, -12345, array(-12345, \F3\PHPCR\PropertyType::LONG)),
			array(\F3\PHPCR\PropertyType::LONG, 0, array(0, \F3\PHPCR\PropertyType::LONG)),
			array(\F3\PHPCR\PropertyType::LONG, 12345, array(12345, \F3\PHPCR\PropertyType::LONG)),
			array(\F3\PHPCR\PropertyType::DOUBLE, -12345, array(-12345.0, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, 0, array(0.0, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, 12345, array(12345.0, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, -12345.6789, array(-12345.6789, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, 0.12345, array(0.12345, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, 12345.6789, array(12345.6789, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::URI, 'http://www.typo3.org', array('http://www.typo3.org', \F3\PHPCR\PropertyType::URI)),
			array(\F3\PHPCR\PropertyType::WEAKREFERENCE, '96bca35d-1ef5-4a47-8b0c-0ddd68507d00', array('96bca35d-1ef5-4a47-8b0c-0ddd68507d00', \F3\PHPCR\PropertyType::WEAKREFERENCE)),
			array(\F3\PHPCR\PropertyType::DATE, new \DateTime('2008-12-24T12:34Z'), array(new \DateTime('2008-12-24T12:34Z'), \F3\PHPCR\PropertyType::DATE)),
			array(\F3\PHPCR\PropertyType::DATE, '2008-12-24T12:34Z', array(new \DateTime('2008-12-24T12:34Z'), \F3\PHPCR\PropertyType::DATE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, '3.4', array(3.4, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, '-3.4', array(-3.4, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::DOUBLE, '3.4E-10', array(3.4E-10, \F3\PHPCR\PropertyType::DOUBLE)),
			array(\F3\PHPCR\PropertyType::LONG, '32345', array(32345, \F3\PHPCR\PropertyType::LONG)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'true', array(TRUE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'trUe', array(TRUE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'TRUE', array(TRUE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'yes', array(FALSE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, '1', array(FALSE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, '', array(FALSE, \F3\PHPCR\PropertyType::BOOLEAN)),
			array(\F3\PHPCR\PropertyType::NAME, 'nt:page', array('nt:page', \F3\PHPCR\PropertyType::NAME)),
			array(\F3\PHPCR\PropertyType::NAME, 'text', array('text', \F3\PHPCR\PropertyType::NAME)),
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider convertibleProperties
	 */
	public function convertValueWorks($propType, $propValue, array $expectedResult) {
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('getNamespacePrefixes')->will($this->returnValue(array('nt')));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);

		$this->assertEquals($expectedResult, $node->_call('convertValue', $propValue, $propType, FALSE));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider convertibleProperties
	 */
	public function convertValueWorksForMultiValues($propType, $propValue, array $expectedResult) {
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('getNamespacePrefixes')->will($this->returnValue(array('nt')));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);

		$this->assertEquals(array(array($expectedResult[0]), $expectedResult[1]), $node->_call('convertValue', array($propValue), $propType, TRUE));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function convertValueRemovesNullFromArrays() {
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);

		$result = $node->_call('convertValue', array(NULL, 'hi there', NULL), \F3\PHPCR\PropertyType::STRING, TRUE);
		$this->assertSame(array('hi there'), $result[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setPropertySetsValue() {
		$mockProperty = $this->getMock('F3\PHPCR\PropertyInterface');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\PropertyInterface', 'someprop')->will($this->returnValue($mockProperty));
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('registerPropertyAsNew')->with($mockProperty);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('convertValue'), array(), '', FALSE);
		$node->expects($this->once())->method('convertValue')->with('value', 'type', FALSE)->will($this->returnValue(array('value', 'type')));
		$node->_set('session', $mockSession);
		$node->_set('objectManager', $mockObjectManager);

		$mockSession->expects($this->once())->method('registerNodeAsDirty')->with($node);

		$node->setProperty('someprop', 'value', 'type');
	}

	/**
	 * Provides test data for setPropertyThrowsExceptionOnUnconvertibleType
	 *
	 * @return array of arrays with parameters for setPropertyThrowsExceptionOnUnconvertibleType()
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function unconvertibleProperties() {
		return array(
			array(\F3\PHPCR\PropertyType::DATE, 'foo'),
			array(\F3\PHPCR\PropertyType::DATE, 5),
			array(\F3\PHPCR\PropertyType::WEAKREFERENCE, 'abc'),
			array(\F3\PHPCR\PropertyType::URI, 'abc'),
			array(\F3\PHPCR\PropertyType::REFERENCE, 'abc'),
			array(\F3\PHPCR\PropertyType::REFERENCE, '12345678-abcd-1234-dcba-1234567890ef')
		);
	}

	/**
	 * @test
	 * @dataProvider unconvertibleProperties
	 * @expectedException \F3\PHPCR\ValueFormatException
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPropertyThrowsExceptionOnUnconvertibleType($propType, $propValue) {
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);

		$node->_call('convertValue', $propValue, $propType, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNewPropertyToNullIsIgnored() {
		$node = $this->getMock('F3\TYPO3CR\Node', array('hasProperty'), array(), '', FALSE);
		$node->expects($this->once())->method('hasProperty')->with('someNewProp')->will($this->returnValue(FALSE));
		$node->setProperty('someNewProp', NULL);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function setExistingPropertyToNullRemovesIt() {
		$mockProperty = $this->getMock('F3\PHPCR\PropertyInterface');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('registerPropertyAsRemoved')->with($mockProperty);

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);
		$node->_set('properties', array('someprop' => $mockProperty, 'otherprop' => $mockProperty));

		$mockSession->expects($this->once())->method('registerNodeAsDirty')->with($node);

		$node->setProperty('someprop', NULL);
		$this->assertEquals(array('otherprop' => $mockProperty), $node->_get('properties'));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithIdentifierUsesIdentifierForNewNode() {
		$identifier = '16bca35d-1ef5-4a47-8b0c-0ddd69507d00';
		$expectedRawData = array(
			'parent' => NULL,
			'name' => 'new-node',
			'nodetype' => 'nt:base',
			'newidentifier' => $identifier
		);
		$mockNewNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNewNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($identifier));

		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeInterface', $expectedRawData, $mockSession)->will($this->returnValue($mockNewNode));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);
		$node->_set('objectManager', $mockObjectManager);
		$node->addNode('new-node', 'nt:base', $identifier);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\ItemExistsException
	 */
	public function addNodeWithUsedIdentifierThrowsException() {
		$identifier = '16bca35d-1ef5-4a47-8b0c-0ddd69507d00';
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('hasIdentifier')->with($identifier)->will($this->returnValue(TRUE));

		$node = $this->getAccessibleMock('F3\TYPO3CR\Node', array('dummy'), array(), '', FALSE);
		$node->_set('session', $mockSession);
		$node->addNode('AgainWithIdentifier', 'nt:base', $identifier);
	}

	/**
	 * @test
	 * @expectedException \F3\PHPCR\RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNodeChecksNameWithIsValidName() {
		$node = $this->getMock('F3\TYPO3CR\Node', array('isValidName'), array(), '', FALSE);
		$node->expects($this->once())->method('isValidName')->with('invalidname')->will($this->returnValue(FALSE));
       	$node->addNode('invalidname', 'nt:base');
	}

}

?>