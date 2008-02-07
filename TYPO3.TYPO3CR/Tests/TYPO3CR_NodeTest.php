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
 * Tests for the Node implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @author 		Karsten Dambekalns <karsten@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_NodeTest extends TYPO3CR_BaseTest {

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
	 * Checks if a Node fetched by getNodeByUUID() returns the expected UUID on getUUID().
	 * @test
	 */
	public function getUUIDReturnsExpectedUUID() {
		$firstExpectedUUID = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';
		$firstNode = $this->session->getNodeByUUID($firstExpectedUUID);
		$this->assertEquals($firstExpectedUUID, $firstNode->getUUID(), 'getUUID() did not return the expected UUID.');
	
		$secondExpectedUUID = '96bca35d-1ef5-4a47-8b0c-0bfc69507d01';
		$secondNode = $this->session->getNodeByUUID($secondExpectedUUID);
		$this->assertEquals($secondExpectedUUID, $secondNode->getUUID(), 'getUUID() did not return the expected UUID.');
	}

	/**
	 * Checks if hasProperties() works as it should.
	 * @test
	 */
	public function hasPropertiesWorks() {
		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d04');
		$this->assertEquals(TRUE, $node->hasProperties(), 'hasProperties() did not return TRUE for a node with properties.');

		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d00');
		$this->assertEquals(FALSE, $node->hasProperties(), 'hasProperties() did not return FALSE for a node without properties.');
	}

	/**
	 * Checks if getProperties() returns the expected result.
	 * @test
	 */
	public function getPropertiesWorks() {
		$propertyIterator = $this->componentManager->getComponent('T3_phpCR_PropertyIteratorInterface');

		$emptyNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d00');
		$noProperties = $emptyNode->getProperties();
		$this->assertType('T3_phpCR_PropertyIteratorInterface', $noProperties, 'getProperties() did not return a PropertyIterator for a node without properties.');

		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d04');
		$properties = $node->getProperties();
		$this->assertType('T3_phpCR_PropertyIteratorInterface', $properties, 'getProperties() did not return a PropertyIterator for a node with properties.');

		$this->assertEquals($propertyIterator, $noProperties, 'getProperties() did not return an empty PropertyIterator for a node without properties.');
		$this->assertNotEquals($propertyIterator, $properties, 'getProperties() returned an empty PropertyIterator for a node with properties.');

		$titleProperty = $this->componentManager->getComponent('T3_phpCR_PropertyInterface', 'title', 'This page is stored in the TYPO3CR...', null, FALSE);
		$propertyIterator->append($titleProperty);
		$this->assertNotEquals($propertyIterator, $properties, 'getProperties() did not return the expected result for a node with properties.');
		/*$subtitleProperty = $this->componentManager->getComponent('T3_phpCR_PropertyInterface', 'subtitle', '... believe it or not!', null, FALSE);
		$propertyIterator->append($subtitleProperty);
		$this->assertEquals($propertyIterator, $properties, 'getProperties() did not return the expected result for a node with properties.');*/
	}

	/**
	 * Checks if getProperty() works
	 * @test
	 */
	public function getPropertyWorks() {
		$newsNodeUUID = '96bca35d-1ef5-4a47-8b0c-0bfc79507d08';
		$newsTitleText = 'News about the TYPO3CR';
		$newsNode = $this->session->getNodeByUUID($newsNodeUUID);
		
		$title = $newsNode->getProperty('title');
		
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (1).');
		
		$titleProperty = $newsNode->getProperty('./title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (2).');
		
		$titleProperty = $newsNode->getProperty('../News/title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (3).');
	}

	/**
	 * Checks if getPrimaryNodeType() returns a NodeType object.
	 * @test
	 */
	public function getPrimaryNodeTypeReturnsANodeType() {
		$this->assertType('T3_phpCR_NodeTypeInterface', $this->rootNode->getPrimaryNodeType(), 'getPrimaryNodeType() in the node did not return a NodeType object.');
	}

	/**
	 * Checks if hasNodes() works es it should.
	 * @test
	 */
	public function hasNodesWorks() {
		$node = $this->session->getNodeByUUID('96bca35d-9ef5-4a47-8b0c-0bfc69507d05');
		$this->assertEquals(TRUE, $node->hasNodes(), 'hasNodes() did not return TRUE for a node with child nodes.');
	
		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc79507d08');
		$this->assertEquals(FALSE, $node->hasNodes(), 'hasNodes() did not return FALSE for a node without child nodes.');
	}

	/**
	 * Checks if getNodes() returns the expected result.
	 * @test
	 */
	public function getNodesWorks() {
		$nodeIterator = $this->componentManager->getComponent('T3_phpCR_NodeIteratorInterface');

		$leaf = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc79507d08');
		$noChildNodes = $leaf->getNodes();
		$this->assertType('T3_phpCR_NodeIteratorInterface', $noChildNodes, 'getNodes() did not return a NodeIterator for a node without child nodes.');

		$node = $this->session->getNodeByUUID('96bca35d-9ef5-4a47-8b0c-0bfc69507d05');
		$childNodes = $node->getNodes();
		$this->assertType('T3_phpCR_NodeIteratorInterface', $childNodes, 'getNodes() did not return a NodeIterator for a node with child nodes.');

		$this->assertEquals($nodeIterator, $noChildNodes, 'getNodes() did not return an empty NodeIterator for a node without child nodes.');
		$this->assertNotEquals($nodeIterator, $childNodes, 'getNodes() returned an empty NodeIterator for a node with child nodes.');

		$node7 = $this->componentManager->getComponent('T3_phpCR_NodeInterface');
		$node7->initializeFromArray(array('id' => '7', 'name' => 'Community', 'pid' => '96bca35d-9ef5-4a47-8b0c-0bfc69507d05', 'nodetype' => '5', 'uuid' => '96bca35d-1ef5-4a47-8b0c-0bfc69507d04'));
		$nodeIterator->append($node7);
		$this->assertNotEquals($nodeIterator, $childNodes, 'getNodes() did not return the expected result for a node with child nodes.');
		$node8 = $this->componentManager->getComponent('T3_phpCR_NodeInterface');
		$node8->initializeFromArray(array('id' => '8', 'name' => 'News', 'pid' => '96bca35d-9ef5-4a47-8b0c-0bfc69507d05', 'nodetype' => '5', 'uuid' => '96bca35d-1ef5-4a47-8b0c-0bfc79507d08'));
		$nodeIterator->append($node8);
		//$this->assertEquals($nodeIterator, $childNodes, 'getNodes() did not return the expected result for a node with child nodes.');
	}

	/**
	 * Checks if getNode() returns the expected result.
	 * @test
	 */
	public function getNodeWorks() {
		$newsNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc79507d08');
		
		$this->assertEquals($newsNode->getNode('../News')->getUUID(), $newsNode->getUUID(), 'getNode() did not return the expected result (1).');
	}

	/**
	 * Tests if getName() returns same as last name returned by getPath()
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getNameWorks() {
		$leaf = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc79507d08');
		$this->assertEquals('News', $leaf->getName(), "getName() must be the same as the last item in the path");
	}

	/**
	 * Test if the ancestor at depth = n, where n is the depth of this
	 * <code>phpCR_Item</code>, returns this <code>phpCR_Node</code> itself.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getAncestorOfNodeDepthWorks() {
		$nodeAtDepth = $this->rootNode->getAncestor($this->rootNode->getDepth());
		$this->assertTrue($this->rootNode->isSame($nodeAtDepth), "The ancestor of depth = n, where n is the depth of this Node must be the item itself.");
	}

	/**
	 * Test if getting the ancestor of depth = n, where n is greater than depth
	 * of this <code>phpCR_Node</code>, throws an <code>phpCR_ItemNotFoundException</code>.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getAncestorOfGreaterDepthWorks() {
		$this->markTestIncomplete('This test has not been implemented yet.');
		try {
			$greaterDepth = $this->rootNode->getDepth() + 1;
			$this->rootNode->getAncestor($greaterDepth);
			$this->fail("Getting ancestor of depth n, where n is greater than depth of this Node must throw an ItemNotFoundException");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Test if getting the ancestor of negative depth throws an ItemNotFoundException.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getAncestorOfNegativeDepthWorks() {
		try {
			$this->rootNode->getAncestor(-1);
			$this->fail("Getting ancestor of depth < 0 must throw an ItemNotFoundException.");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Tests if isSame() returns true when retrieving an item through different
	 * sessions
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @todo try to fetch root node through other session
	 * @test
	 */
	public function isSameWorks() {
			// fetch root node "by hand"
		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d00');
		$this->assertTrue($this->rootNode->isSame($testNode), "isSame(Item item) must return true for the same item.");
	}

	/**
	 * Tests if getParent() returns parent node
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getParentWorks() {
		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d01');
		if ($testNode == null) {
			$this->fail("Workspace does not have sufficient content to run this test.");
		}

		$this->assertTrue($this->rootNode->isSame($testNode->getParent()), "getParent() of a child node does not return the parent node.");
	}

	/**
	 * Tests if getParent() of root throws an phpCR_ItemNotFoundException
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getParentOfRootFails() {
		try {
			$this->session->getRootNode()->getParent();
			$this->fail("getParent() of root must throw an ItemNotFoundException.");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Tests if depth of root is 0 and depth of a sub node of root is 1
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getDepthWorks() {
		$this->assertEquals(0, $this->session->getRootNode()->getDepth(), "getDepth() of root must be 0");

		$testNode = $this->session->getNodeByUUID('96fca35d-1ef5-4a47-8b0c-0bfc69507d02');
		$this->assertEquals(2, $testNode->getDepth(), "getDepth() of subchild must be 2");

		for ($it = $this->session->getRootNode()->getNodes(); $it->hasNext(); ) {
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
		$this->assertSame($this->rootNode->getSession(), $this->session, "getSession must return the Session through which the Node was acquired.");
	}

	/**
	 * Tests if isNode() returns true
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function isNodeReturnsTrue() {
		$this->assertTrue($this->rootNode->isNode(), "isNode() must return true.");
	}

	/**
	 * Tests if getPath() returns the correct path.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getPathWithoutSameNameSiblingsWorks() {
		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0bfc69507d01');

		$path = $this->rootNode->getPath() . $testNode->getName();
		$this->assertEquals($path, $testNode->getPath(), "getPath() returns wrong result");
	}

	/**
	 * Test if addNode() returns a Node.
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @test
	 */
	public function addNodePathReturnsANode() {

		$newNode = $this->rootNode->addNode('User');
		$this->assertType('T3_phpCR_NodeInterface', $newNode, 'addNode() does not return an object of type T3_phpCR_NodeInterface.');
		$this->assertTrue($this->rootNode->isSame($newNode->getParent()), 'After addNode() calling getParent() from the new node does not return the parent node.');

		$newNode->remove();
		$this->rootNode->save();
	}

	/**
	 * Test if addNode(Content/./Categories/Pages/User) returns a Node.
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @test
	 */
	public function addNodeWithRelPathReturnsANode() {

		$newNode = $this->rootNode->addNode('Content/./Categories/Pages/User');
		$this->assertType('T3_phpCR_NodeInterface', $newNode, 'Function: addNode() - returns not an object from type T3_phpCR_NodeInterface.');
		$this->assertType('T3_phpCR_NodeInterface', $newNode->getParent(), 'Function: addNode() - getParent() return an parent Node.');

		$newNode->remove();
		$this->rootNode->save();
	}

	/**
	 * Test session save()
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @test
	 */
	public function sessionSaveWorks() {
		$newNode1 = $this->rootNode->addNode('User');
		$newNode2 = $this->rootNode->addNode('Content/./Categories/Pages/User');

		$this->session->save();

		$newNode1 = $this->session->getNodeByUUID($newNode1->getUUID());
		$this->assertType('T3_phpCR_NodeInterface', $newNode1, 'Function: save() - Nodes are not persisted in the CR.');
		$newNode2 = $this->session->getNodeByUUID($newNode2->getUUID());
		$this->assertType('T3_phpCR_NodeInterface', $newNode2, 'Function: save() - Nodes are not persisted in the CR.');

			// delete nodes again
		$newNode1->remove();
		$newNode2->remove();
		$this->session->save();
	}

	/**
	 * Test set property
	 * @test
	 */
	public function setPropertyWorks() {
		$testPropertyNode = $this->session->getRootNode()->addNode('TestPropertyNode');
		$testPropertyNode->setProperty('title', 'YEAH, it works!');
		$this->assertEquals($this->session->getItem('/TestPropertyNode/title')->getString(), 'YEAH, it works!', 'Transient storage does not work, title should be "YEAH, it works!", but is "' . $this->session->getItem('/TestPropertyNode/title')->getString() . '"');
		$this->session->save();
		$this->assertFalse($this->session->getItem('/TestPropertyNode/title')->isNew(), 'isNew() not correctly set. (Needs to be false)');

		$testPropertyNode->setProperty('title', 'YEAH, it still works!');
		$this->assertEquals($this->session->getItem('/TestPropertyNode/title')->getString(), 'YEAH, it still works!', 'Transient storage does not work, title should be "YEAH, it still works!", but is "' . $this->session->getItem('/TestPropertyNode/title')->getString() . '"');
		$this->assertTrue($this->session->getItem('/TestPropertyNode/title')->isModified(), 'isModified() not correctly set. (Needs to be true)');
		$this->session->save();
		$this->assertFalse($this->session->getItem('/TestPropertyNode/title')->isModified(), 'isModified() not correctly set. (Needs to be false)');

		$testPropertyNode->remove();
		$this->session->save();
	}
}
?>
