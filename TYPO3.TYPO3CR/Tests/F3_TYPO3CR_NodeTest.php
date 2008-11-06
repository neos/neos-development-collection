<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR;

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

require_once('Fixtures/F3_TYPO3CR_MockStorageBackend.php');

/**
 * Tests for the Node implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NodeTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::TYPO3CR::Node
	 */
	protected $rootNode;

	/**
	 * @var F3::TYPO3CR::MockStorageBackend
	 */
	protected $mockStorageBackend;

	/**
	 * @var F3::TYPO3CR::Session
	 */
	protected $session;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$mockRepository = $this->getMock('F3::PHPCR::RepositoryInterface');
		$this->mockStorageBackend = new F3::TYPO3CR::MockStorageBackend();
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
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d07' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d07',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'Refparent'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d15' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d15',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'Refparent'
				),
			)
		);
		$this->mockStorageBackend->rawPropertiesByIdentifierGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					array(
						'name' => 'title',
						'value' => 'News about the TYPO3CR',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => F3::PHPCR::PropertyType::STRING
					)
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d15' => array(
					array(
						'name' => 'ref',
						'value' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => F3::PHPCR::PropertyType::REFERENCE
					),
					array(
						'name' => 'wrongref',
						'value' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d07',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => F3::PHPCR::PropertyType::REFERENCE
					),
					array(
						'name' => 'weakref',
						'value' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => F3::PHPCR::PropertyType::WEAKREFERENCE
					),
					array(
						'name' => 'wrongweakref',
						'value' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d07',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => F3::PHPCR::PropertyType::REFERENCE
					),
				)
			)
		);

		$this->session = new F3::TYPO3CR::Session('default', $mockRepository, $this->mockStorageBackend, $this->componentFactory);
		$this->rootNode = $this->session->getRootNode();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function newNodeIsMarkedAsNew() {
		$newNode = $this->rootNode->addNode('User');
		$this->assertTrue($newNode->isNew(), 'freshly created node is not marked new');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function newNodeIsNotMarkedAsModified() {
		$newNode = $this->rootNode->addNode('User');
		$this->assertFalse($newNode->isModified(), 'freshly created node is marked modified');
	}

	/**
	 * Checks if a Node fetched by getNodeByIdentifier() returns the expected Identifier on getIdentifier().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getIdentifierReturnsExpectedIdentifier() {
		$firstExpectedIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$firstNode = $this->session->getNodeByIdentifier($firstExpectedIdentifier);
		$this->assertEquals($firstExpectedIdentifier, $firstNode->getIdentifier(), 'getIdentifier() did not return the expected Identifier.');

		$secondExpectedIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$secondNode = $this->session->getNodeByIdentifier($secondExpectedIdentifier);
		$this->assertEquals($secondExpectedIdentifier, $secondNode->getIdentifier(), 'getIdentifier() did not return the expected Identifier.');
	}

	/**
	 * Checks if getReferences returns nothing when called on a node that is not referenced
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getReferencesReturnsNothingOnUnReferencedNode() {
		$node = $this->session->getRootNode();
		$references = $node->getReferences();
		$this->assertEquals(0, $references->getSize());
	}

	/**
	 * Checks if getReferences returns nothing when called with a non-existant property name
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getReferencesReturnsNothingOnNonExistantReferenceName() {
		$expectedRefTarget = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$node = $this->session->getNodeByIdentifier($expectedRefTarget);
		$references = $node->getReferences('notref');
		$this->assertEquals(0, $references->getSize());
	}

	/**
	 * Checks if getReferences returns exactly the one reference referencing the given node when called without a $name parameter
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getReferencesReturnsReferenceWithoutNameParameter() {
		$expectedRefTarget = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$node = $this->session->getNodeByIdentifier($expectedRefTarget);
		$references = $node->getReferences();
		$this->assertEquals(1, $references->getSize());
		$reference = $references->nextProperty();
		$this->assertEquals($reference->getValue()->getString(), $expectedRefTarget);
		$this->assertEquals($reference->getType(), F3::PHPCR::PropertyType::REFERENCE);
	}

	/**
	 * Checks if getReferences returns exactly the one reference referencing the given node when called with the correct $name parameter
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getReferencesReturnsReferenceWithNameParameter() {
		$expectedRefTarget = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$node = $this->session->getNodeByIdentifier($expectedRefTarget);
		$references = $node->getReferences('ref');
		$this->assertEquals(1, $references->getSize());
		$reference = $references->nextProperty();
		$this->assertEquals($reference->getValue()->getString(), $expectedRefTarget);
		$this->assertEquals($reference->getType(), F3::PHPCR::PropertyType::REFERENCE);
	}

	/**
	 * Checks if getWeakReferences returns nothing when called on a node that is not referenced
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getWeakReferencesReturnsNothingOnUnReferencedNode() {
		$node = $this->session->getRootNode();
		$references = $node->getWeakReferences();
		$this->assertEquals(0, $references->getSize());
	}

	/**
	 * Checks if getWeakReferences returns nothing when called with a non-existant property name
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getWeakReferencesReturnsNothingOnNonExistantReferenceName() {
		$expectedRefTarget = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$node = $this->session->getNodeByIdentifier($expectedRefTarget);
		$references = $node->getWeakReferences('notweakref');
		$this->assertEquals(0, $references->getSize());
	}

	/**
	 * Checks if getWeakReferences returns exactly the one reference referencing the given node when called without a $name parameter
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getWeakReferencesReturnsReferenceWithoutNameParameter() {
		$expectedRefTarget = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$node = $this->session->getNodeByIdentifier($expectedRefTarget);
		$references = $node->getWeakReferences();
		$this->assertEquals(1, $references->getSize());
		$reference = $references->nextProperty();
		$this->assertEquals($reference->getValue()->getString(), $expectedRefTarget);
		$this->assertEquals($reference->getType(), F3::PHPCR::PropertyType::WEAKREFERENCE);
	}

	/**
	 * Checks if getWeakReferences returns exactly the one reference referencing the given node when called with the correct $name parameter
	 *
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getWeakReferencesReturnsReferenceWithNameParameter() {
		$expectedRefTarget = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$node = $this->session->getNodeByIdentifier($expectedRefTarget);
		$references = $node->getWeakReferences('weakref');
		$this->assertEquals(1, $references->getSize());
		$reference = $references->nextProperty();
		$this->assertEquals($reference->getValue()->getString(), $expectedRefTarget);
		$this->assertEquals($reference->getType(), F3::PHPCR::PropertyType::WEAKREFERENCE);
	}

	/**
	 * Checks if hasProperties() works as it should.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasPropertiesWorks() {
		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertTrue($node->hasProperties(), 'hasProperties() did not return TRUE for a node with properties.');

		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertFalse($node->hasProperties(), 'hasProperties() did not return FALSE for a node without properties.');
	}

	/**
	 * Checks if getProperties() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertiesWorks() {
		$emptyNode = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$noProperties = $emptyNode->getProperties();
		$this->assertType('F3::PHPCR::PropertyIteratorInterface', $noProperties, 'getProperties() did not return a PropertyIterator for a node without properties.');

		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$properties = $node->getProperties();
		$this->assertType('F3::PHPCR::PropertyIteratorInterface', $properties, 'getProperties() did not return a PropertyIterator for a node with properties.');

		$propertyIterator = new F3::TYPO3CR::PropertyIterator;
		$this->assertEquals(0, $propertyIterator->getSize(), 'getProperties() did not return an empty PropertyIterator for a node without properties.');
		$this->assertNotEquals(1, $propertyIterator->getSize(), 'getProperties() returned an empty PropertyIterator for a node with properties.');

			// we don't compare the iterators directly here, as this hits the memory limit hard. really hard.
		$titleProperty = $this->componentFactory->create('F3::PHPCR::PropertyInterface', 'title', 'News about the TYPO3CR', F3::PHPCR::PropertyType::STRING, $node, $this->session);
		$this->assertEquals($titleProperty->getString(), $properties->nextProperty()->getString(), 'getProperties() did not return the expected property.');
	}

	/**
	 * Checks if hasProperty() works with various paths
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasPropertyWorks() {
		$newsNodeIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$newsNode = $this->session->getNodeByIdentifier($newsNodeIdentifier);

		$this->assertTrue($newsNode->hasProperty('title'), 'Expected property was not found (1).');
		$this->assertTrue($newsNode->hasProperty('./title'), 'Expected property was not found (2).');
		$this->assertTrue($newsNode->hasProperty('../News/title'), 'Expected property was not found (3).');

		$this->assertFalse($newsNode->hasProperty('nonexistant'), 'Unxpected property was found (1).');
		$this->assertFalse($newsNode->hasProperty('./nonexistant'), 'Unexpected property wasfound (2).');
	}

	/**
	 * Checks if getProperty() works with various paths
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertyWorks() {
		$newsNodeIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$newsTitleText = 'News about the TYPO3CR';
		$newsNode = $this->session->getNodeByIdentifier($newsNodeIdentifier);

		$title = $newsNode->getProperty('title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (1).');

		$title = $newsNode->getProperty('./title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (2).');

		$title = $newsNode->getProperty('../News/title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (3).');
	}

	/**
	 * Checks if getPrimaryNodeType() returns a NodeType object.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPrimaryNodeTypeReturnsANodeType() {
		$this->assertType('F3::PHPCR::NodeType::NodeTypeInterface', $this->rootNode->getPrimaryNodeType(), 'getPrimaryNodeType() in the node did not return a NodeType object.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPrimaryNodeTypeReturnsExpectedNodeType() {
		$this->assertEquals('nt:base', $this->rootNode->getPrimaryNodeType()->getName(), 'getPrimaryNodeType() in the node did not return the expected NodeType.');
	}

	/**
	 * Checks if hasNodes() works as it should.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNodesWorks() {
		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertTrue($node->hasNodes(), 'hasNodes() did not return TRUE for a node with child nodes.');

		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertFalse($node->hasNodes(), 'hasNodes() did not return FALSE for a node without child nodes.');
	}

	/**
	 * Checks if getNodes() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodesWorks() {
		$leaf = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$noChildNodes = $leaf->getNodes();
		$this->assertType('F3::PHPCR::NodeIteratorInterface', $noChildNodes, 'getNodes() did not return a NodeIterator for a node without child nodes.');

		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$childNodes = $node->getNodes();
		$this->assertType('F3::PHPCR::NodeIteratorInterface', $childNodes, 'getNodes() did not return a NodeIterator for a node with child nodes.');

		$this->assertEquals(0, $noChildNodes->getSize(), 'getNodes() did not return an empty NodeIterator for a node without child nodes.');
		$this->assertNotEquals(0, $childNodes->getSize(), 'getNodes() returned an empty NodeIterator for a node with child nodes.');

		$this->assertEquals('96bca35d-1ef5-4a47-8b0c-0ddd68507d00', $childNodes->nextNode()->getIdentifier(), 'getNodes() did not return the expected result for a node with child nodes.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNodeReturnsTrueIfNodeExists() {
		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertTrue($node->hasNode('News'), 'hasNode() did not return TRUE for a node with the given child node.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNodeReturnsFalseIfNodeDoesNotExist() {
		$node = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertFalse($node->hasNode('nonExistingNode'), 'hasNode() did not return FALSE for a node without the given child node.');
	}

	/**
	 * Checks if getNode() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeWorks() {
		$newsNode = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals($newsNode->getNode('../News')->getIdentifier(), $newsNode->getIdentifier(), 'getNode() did not return the expected result.');
	}

	/**
	 * Tests if getName() returns same as last name returned by getPath()
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getNameWorks() {
		$leaf = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals('News', $leaf->getName(), "getName() must be the same as the last item in the path");
	}

	/**
	 * Test if the ancestor at depth = n, where n is the depth of this
	 * item, returns this node itself.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getAncestorOfNodeDepthWorks() {
		$node = $this->rootNode->getNode('Content');
		$nodeAtDepth = $node->getAncestor($node->getDepth());
		$this->assertTrue($node->isSame($nodeAtDepth), "The ancestor of depth = n, where n is the depth of this Node must be the item itself.");
	}

	/**
	 * Test if getting the ancestor of depth = n, where n is greater than depth
	 * of this node, throws an PHPCR_ItemNotFoundException for a sub node.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException F3::PHPCR::ItemNotFoundException
	 */
	public function getAncestorOfGreaterDepthOnSubNodeThrowsException() {
		$node = $this->rootNode->getNode('Content/News');
		$node->getAncestor($node->getDepth() + 1);
	}

	/**
	 * Test if getting the ancestor of depth = n, where n is greater than depth
	 * of this node, throws an PHPCR_ItemNotFoundException for a root node.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException F3::PHPCR::ItemNotFoundException
	 */
	public function getAncestorOfGreaterDepthOnRootNodeThrowsException() {
		$node = $this->rootNode;
		$node->getAncestor($node->getDepth() + 1);
	}

	/**
	 * Test if getting the ancestor of negative depth throws an ItemNotFoundException.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 * @expectedException F3::PHPCR::ItemNotFoundException
	 */
	public function getAncestorOfNegativeDepthThrowsException() {
		$this->rootNode->getAncestor(-1);
	}

	/**
	 * Tests if isSame() returns FALSE when retrieving an item through different
	 * sessions
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @todo try to fetch root node through other session
	 * @test
	 */
	public function isSameReturnsTrueForSameNodes() {
			// fetch root node "by hand"
		$testNode = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d00');
		$this->assertTrue($this->rootNode->isSame($testNode), "isSame() must return FALSE for the same item.");
	}

	/**
	 * Tests if getParent() returns parent node
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getParentReturnsExpectedNode() {
		$testNode = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertTrue($this->rootNode->isSame($testNode->getParent()), "getParent() of a child node does not return the parent node.");
	}

	/**
	 * Tests if getParent() of root throws an PHPCR_ItemNotFoundException
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 * @expectedException F3::PHPCR::ItemNotFoundException
	 */
	public function getParentOfRootFails() {
		$this->rootNode->getParent();
	}

	/**
	 * Tests if depth of root is 0, depth of a sub node of root is 1, and sub-sub nodes have a depth of 2
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getDepthReturnsCorrectDepth() {
		$this->assertEquals(0, $this->rootNode->getDepth(), "getDepth() of root must be 0");

		$testNode = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals(2, $testNode->getDepth(), "getDepth() of subchild must be 2");

		for ($it = $this->rootNode->getNodes(); $it->hasNext(); ) {
			$this->assertEquals(1, $it->next()->getDepth(), "getDepth() of child node of root must be 1");
		}
	}

	/**
	 * Tests if getSession() is same as through which the Item was acquired
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getSessionReturnsSourceSession() {
		$this->assertSame($this->rootNode->getSession(), $this->session, "getSession() must return the Session through which the Node was acquired.");
	}

	/**
	 * Tests if isNode() returns FALSE
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function isNodeReturnsTrue() {
		$this->assertTrue($this->rootNode->isNode(), "isNode() must return TRUE.");
	}

	/**
	 * Tests if getPath() returns the correct path.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPathWithoutSameNameSiblingsWorks() {
		$testNode = $this->session->getNodeByIdentifier('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals('/Content/News', $testNode->getPath(), "getPath() returns wrong result");
	}

	/**
	 * Test if addNode() returns a Node.
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeReturnsANode() {
		$newNode = $this->rootNode->addNode('User');
		$this->assertType('F3::PHPCR::NodeInterface', $newNode, 'addNode() does not return an object of type F3::PHPCR::NodeInterface.');
		$this->assertTrue($this->rootNode->isSame($newNode->getParent()), 'After addNode() calling getParent() from the new node does not return the expected parent node.');
	}

	/**
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithSimpleRelativePathReturnsANode() {
		$newNode = $this->rootNode->addNode('SomeItem');
		$this->assertType('F3::PHPCR::NodeInterface', $newNode, 'Function: addNode() - returns not an object from type F3::PHPCR::NodeInterface.');
	}

	/**
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithComplexRelativePathReturnsANode() {
		$newNode = $this->rootNode->addNode('Content/./News/SomeItem');
		$this->assertType('F3::PHPCR::NodeInterface', $newNode, 'Function: addNode() - returns not an object from type F3::PHPCR::NodeInterface.');
	}

	/**
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithComplexRelativePathReturnsNodeWithExpectedParent() {
		$newNode = $this->rootNode->addNode('Content/./News/SomeItem');
		$expectedParentNode = $this->rootNode->getNode('Content/News');
		$this->assertTrue($expectedParentNode->isSame($newNode->getParent()), 'After addNode() calling getParent() from the new node does not return the expected parent node.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addedNodeIsVisibleInSession() {
		$newNode = $this->rootNode->addNode('User');

		$retrievedNode = $this->session->getNodeByIdentifier($newNode->getIdentifier());
		$this->assertSame('User', $retrievedNode->getName(), 'added node is invisible to session');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeSetsModifiedStatusOfNode() {
		$this->rootNode->addNode('User');
		$this->assertTrue($this->rootNode->isModified(), 'addNode does not mark parent as modified');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeRegistersNodeAsNewInSession() {
		$mockRepository = $this->getMock('F3::PHPCR::RepositoryInterface');
		$mockSession = $this->getMock('F3::TYPO3CR::Session', array('registerNodeAsNew'), array('default', $mockRepository, $this->mockStorageBackend, $this->componentFactory));
		$mockSession->expects($this->once())->method('registerNodeAsNew');
		$rootNode = $mockSession->getRootNode();
		$rootNode->addNode('User');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeRegistersParentNodeAsDirtyInSession() {
		$mockRepository = $this->getMock('F3::PHPCR::RepositoryInterface');
		$mockSession = $this->getMock('F3::TYPO3CR::Session', array('registerNodeAsDirty'), array('default', $mockRepository, $this->mockStorageBackend, $this->componentFactory));
		$mockSession->expects($this->once())->method('registerNodeAsDirty');
		$rootNode = $mockSession->getRootNode();
		$rootNode->addNode('User');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeNodeRegistersNodeAsRemovedInSession() {
		$mockRepository = $this->getMock('F3::PHPCR::RepositoryInterface');
		$mockSession = $this->getMock('F3::TYPO3CR::Session', array('registerNodeAsRemoved'), array('default', $mockRepository, $this->mockStorageBackend, $this->componentFactory));
		$mockSession->expects($this->once())->method('registerNodeAsRemoved');
		$rootNode = $mockSession->getRootNode();
		$node = $rootNode->addNode('User');
		$node->remove();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeNodeRemovesNode() {
		$node = $this->rootNode->addNode('SomeNode');
		$node->remove();
		try {
			$this->rootNode->getNode('SomeNode');
			$this->fail('Removed node was still available');
		} catch (F3::PHPCR::PathNotFoundException $e) {}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeNodeRemovesNodeInSession() {
		$node = $this->rootNode->addNode('SomeNode');
		$node->remove();
		try {
			$this->session->getNodeByIdentifier($node->getIdentifier());
			$this->fail('Removed node was still available');
		} catch (F3::PHPCR::ItemNotFoundException $e) {}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function setPropertySetsModifiedStatusOfNode() {
		$this->rootNode->setProperty('someprop', 1, F3::PHPCR::PropertyType::LONG);
		$this->assertTrue($this->rootNode->isModified(), 'setProperty does not mark parent as modified');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function setPropertyIsVisibleToNode() {
		$this->rootNode->setProperty('someprop', 'somePropValue', F3::PHPCR::PropertyType::STRING);
		$this->assertTrue($this->rootNode->hasProperty('someprop'), 'hasProperty returns FALSE for freshly added property');
	}


	/**
	 * Provides test data for setPropertySetsValue
	 *
	 * @return array of arrays with parameters for setPropertySetsValue()
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function convertableProperties() {
		return array(
			array(F3::PHPCR::PropertyType::UNDEFINED, 'someValue', new F3::TYPO3CR::Value('someValue', F3::PHPCR::PropertyType::STRING)),
			array(F3::PHPCR::PropertyType::UNDEFINED, TRUE, new F3::TYPO3CR::Value(TRUE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::UNDEFINED, FALSE, new F3::TYPO3CR::Value(FALSE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::UNDEFINED, 12345, new F3::TYPO3CR::Value(12345, F3::PHPCR::PropertyType::LONG)),
			array(F3::PHPCR::PropertyType::STRING, 'someValue', new F3::TYPO3CR::Value('someValue', F3::PHPCR::PropertyType::STRING)),
			array(F3::PHPCR::PropertyType::STRING, 12345, new F3::TYPO3CR::Value('12345', F3::PHPCR::PropertyType::STRING)),
			array(F3::PHPCR::PropertyType::STRING, 12345.6, new F3::TYPO3CR::Value('12345.6', F3::PHPCR::PropertyType::STRING)),
			array(F3::PHPCR::PropertyType::STRING, TRUE, new F3::TYPO3CR::Value('true', F3::PHPCR::PropertyType::STRING)),
			array(F3::PHPCR::PropertyType::STRING, FALSE, new F3::TYPO3CR::Value('false', F3::PHPCR::PropertyType::STRING)),
			array(F3::PHPCR::PropertyType::BOOLEAN, TRUE, new F3::TYPO3CR::Value(TRUE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::BOOLEAN, FALSE, new F3::TYPO3CR::Value(FALSE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::LONG, -12345, new F3::TYPO3CR::Value(-12345, F3::PHPCR::PropertyType::LONG)),
			array(F3::PHPCR::PropertyType::LONG, 0, new F3::TYPO3CR::Value(0, F3::PHPCR::PropertyType::LONG)),
			array(F3::PHPCR::PropertyType::LONG, 12345, new F3::TYPO3CR::Value(12345, F3::PHPCR::PropertyType::LONG)),
			array(F3::PHPCR::PropertyType::DOUBLE, -12345, new F3::TYPO3CR::Value(-12345.0, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, 0, new F3::TYPO3CR::Value(0.0, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, 12345, new F3::TYPO3CR::Value(12345.0, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, -12345.6789, new F3::TYPO3CR::Value(-12345.6789, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, 0.12345, new F3::TYPO3CR::Value(0.12345, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, 12345.6789, new F3::TYPO3CR::Value(12345.6789, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::URI, 'http://www.typo3.org', new F3::TYPO3CR::Value('http://www.typo3.org', F3::PHPCR::PropertyType::URI)),
			array(F3::PHPCR::PropertyType::WEAKREFERENCE, '96bca35d-1ef5-4a47-8b0c-0ddd68507d00', new F3::TYPO3CR::Value('96bca35d-1ef5-4a47-8b0c-0ddd68507d00', F3::PHPCR::PropertyType::WEAKREFERENCE)),
			array(F3::PHPCR::PropertyType::DATE, new DateTime('2008-12-24T12:34Z'), new F3::TYPO3CR::Value(new DateTime('2008-12-24T12:34+0000'), F3::PHPCR::PropertyType::DATE)),
			array(F3::PHPCR::PropertyType::DATE, '2008-12-24T12:34Z', new F3::TYPO3CR::Value(new DateTime('2008-12-24T12:34+0000'), F3::PHPCR::PropertyType::DATE)),
			array(F3::PHPCR::PropertyType::DOUBLE, '3.4', new F3::TYPO3CR::Value(3.4, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, '-3.4', new F3::TYPO3CR::Value(-3.4, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::DOUBLE, '3.4E-10', new F3::TYPO3CR::Value(3.4E-10, F3::PHPCR::PropertyType::DOUBLE)),
			array(F3::PHPCR::PropertyType::LONG, '32345', new F3::TYPO3CR::Value(32345, F3::PHPCR::PropertyType::LONG)),
			array(F3::PHPCR::PropertyType::BOOLEAN, 'true', new F3::TYPO3CR::Value(TRUE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::BOOLEAN, 'trUe', new F3::TYPO3CR::Value(TRUE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::BOOLEAN, 'TRUE', new F3::TYPO3CR::Value(TRUE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::BOOLEAN, 'yes', new F3::TYPO3CR::Value(FALSE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::BOOLEAN, '1', new F3::TYPO3CR::Value(FALSE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::BOOLEAN, '', new F3::TYPO3CR::Value(FALSE, F3::PHPCR::PropertyType::BOOLEAN)),
			array(F3::PHPCR::PropertyType::NAME, 'nt:page', new F3::TYPO3CR::Value('nt:page', F3::PHPCR::PropertyType::NAME)),
			array(F3::PHPCR::PropertyType::NAME, 'text', new F3::TYPO3CR::Value('text', F3::PHPCR::PropertyType::NAME)),
			array(F3::PHPCR::PropertyType::REFERENCE, '96bca35d-1ef5-4a47-8b0c-0ddd69507d00', new F3::TYPO3CR::Value('96bca35d-1ef5-4a47-8b0c-0ddd69507d00', F3::PHPCR::PropertyType::REFERENCE))
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider convertableProperties
	 */
	public function setPropertySetsValue($propType, $propValue, $expectedResult) {
		$this->rootNode->setProperty('someprop', $propValue, $propType);
		$this->assertEquals($expectedResult, $this->rootNode->getProperty('someprop')->getValue(), 'unexpected value returned for freshly added property');
	}

	/**
	 * Provides test data for setPropertyThrowsExceptionOnUncovertableType
	 *
	 * @return array of arrays with parameters for setPropertyThrowsExceptionOnUncovertableType()
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function unconvertableProperties() {
		return array(
			array(F3::PHPCR::PropertyType::DATE, 'foo'),
			array(F3::PHPCR::PropertyType::DATE, 5),
			array(F3::PHPCR::PropertyType::WEAKREFERENCE, 'abc'),
			array(F3::PHPCR::PropertyType::URI, 'abc'),
			array(F3::PHPCR::PropertyType::REFERENCE, 'abc'),
			array(F3::PHPCR::PropertyType::REFERENCE, '12345678-abcd-1234-dcba-1234567890ef')
		);
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider unconvertableProperties
	 */
	public function setPropertyThrowsExceptionOnUncovertableType($propType, $propValue) {
		try {
			$this->rootNode->setProperty('someprop', $propValue, $propType);
			$this->fail('setProperty() must throw exception if the given value is not convertable to the given type');
		} catch (F3::PHPCR::ValueFormatException $e) {}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNewPropertyToNullIsIgnored() {
		$this->rootNode->setProperty('someNewProp', NULL);
		$this->assertFalse($this->rootNode->hasProperty('someNewProp'), 'Property added with NULL value was not ignored');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function setPropertyCompactsArraysContainingNull() {
		$this->rootNode->setProperty('newPropFromArray', array(NULL, 'hi there', NULL), F3::PHPCR::PropertyType::STRING);
		$this->assertTrue(count($this->rootNode->getProperty('newPropFromArray')->getValues()) == 1, 'setProperty() did not remove NULL values from an array');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function setExistingPropertyToNullRemovesIt() {
		$this->rootNode->setProperty('someprop', 'somePropValue', F3::PHPCR::PropertyType::STRING);
		$this->rootNode->setProperty('someprop', NULL);
		$this->assertFalse($this->rootNode->hasProperty('someprop'), 'hasProperty returns TRUE for removed property');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeOnRootNodeThrowsException() {
		try {
			$this->rootNode->remove();
			$this->fail('Root node must not be removable');
		} catch (F3::PHPCR::NodeType::ConstraintViolationException $e) {}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithIdentifierAcceptsIdentifier() {
		$identifier = '16bca35d-1ef5-4a47-8b0c-0ddd69507d00';
		$newNode = $this->rootNode->addNode('WithIdentifier', NULL, $identifier);
		$this->assertEquals($identifier, $newNode->getIdentifier(), 'The new node did not have the expected identifier.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithIdentifierRegistersNodeAsNewInSession() {
		$mockRepository = $this->getMock('F3::PHPCR::RepositoryInterface');
		$mockSession = $this->getMock('F3::TYPO3CR::Session', array('registerNodeAsNew'), array('default', $mockRepository, $this->mockStorageBackend, $this->componentFactory));
		$mockSession->expects($this->once())->method('registerNodeAsNew');
		$rootNode = $mockSession->getRootNode();
		$rootNode->addNode('WithIdentifier', NULL, '16bca35d-1ef5-4a47-8b0c-0ddd69507d00');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException F3::PHPCR::ItemExistsException
	 */
	public function addNodeWithUsedIdentifierRejectsIdentifier() {
		$identifier = '16bca35d-1ef5-4a47-8b0c-0ddd69507d00';
		$this->rootNode->addNode('WithIdentifier', NULL, $identifier);
		$this->rootNode->addNode('AgainWithIdentifier', NULL, $identifier);
	}

	/**
	 * Data provider for addNodeRejectsInvalidNames()
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidLocalNames() {
		$nonSpace = array(
			array('/'),
			array(':'),
			array('['),
			array(']'),
			array('*'),
			array('|'),
			array(' '),
			array(chr(9)), // tab
			array(chr(10)), // line feed
			array(chr(13)) // carriage return
		);

		$oneChar = $nonSpace;
		$oneChar[] = array('');
		$oneChar[] = array('.');

		$twoChar = array();
		foreach ($oneChar as $character) {
			$twoChar[] = array($character[0] . $character[0]);
			$twoChar[] = array('.' . $character[0]);
			$twoChar[] = array($character[0] . '.');
		}

		$multiChar = array();
		foreach ($nonSpace as $character) {
			$multiChar[] = array($character[0] . $character[0] . $character[0]);
			$multiChar[] = array($character[0] . 'middle' . $character[0]);
		}

		return array_merge($oneChar, $twoChar, $multiChar);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @dataProvider invalidLocalNames
	 * @expectedException F3::PHPCR::RepositoryException
	 */
	public function addNodeRejectsInvalidNames($name) {
		$newNode = $this->rootNode->addNode($name);
	}

	/**
	 * Data provider for addNodeAcceptsValidNames(), tests some not too
	 * obvious valid names.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validLocalNames() {
		return array(
			array('. .'),
			array('...'),
			array('.a'),
			array('a.'),
			array('id')
		);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @dataProvider validLocalNames
	 */
	public function addNodeAcceptsValidNames($name) {
		$newNode = $this->rootNode->addNode($name);
	}

}
?>