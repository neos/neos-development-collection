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

require_once('Fixtures/T3_TYPO3CR_MockStorageAccess.php');

/**
 * Tests for the Session implementation of TYPO3CR
 *
 * @package   phpCR
 * @version   $Id$
 * @author    Karsten Dambekalns <karsten@typo3.org>
 * @copyright Copyright belongs to the respective authors
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_SessionTest extends T3_Testing_BaseTestCase {

	/**
	 * Checks if getRepository returns the Repository object used to create the Session object.
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getRepositoryReturnsTheCreatingRepository() {
		$mockRepository = $this->getMock('T3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('T3_TYPO3CR_StorageAccessInterface');
		$mockItemManager = $this->getMock('T3_TYPO3CR_ItemManager', array(), array(), '', FALSE);
		
		$session = new T3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager, $mockItemManager);
		$this->assertSame($mockRepository, $session->getRepository(), 'The session did not return the repository from which it was created.');
	}

	/**
	 * Checks if getWorkspace returns a Workspace object.
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getWorkspaceAlwaysReturnsTheAssociatedWorkspace() {
		$mockRepository = $this->getMock('T3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('T3_TYPO3CR_StorageAccessInterface');
		$mockItemManager = $this->getMock('T3_TYPO3CR_ItemManager', array(), array(), '', FALSE);

		$session = new T3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager, $mockItemManager);
		
		$this->assertType('T3_phpCR_WorkspaceInterface', $session->getWorkspace(), 'The session did not return a workspace object on getWorkspace().');
	}

	/** 
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getRootNodeReturnsRootModeOfDefaultWorkspace() {
		$mockRepository = $this->getMock('T3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockItemManager = $this->getMock('T3_TYPO3CR_ItemManager', array(), array(), '', FALSE);

		$mockStorageAccess = new T3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'pid' => 0,
				'nodetype' => 0,
				'name' => ''
			)
		);
		
		$session = new T3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager, $mockItemManager);
		$rootNode = $session->getRootNode();
		
		$this->assertEquals('96bca35d-1ef5-4a47-8b0c-0ddd69507d00', $rootNode->getUUID(), 'The UUID of the retrieved root node is not as expected.');
	}
	
	
/*** TESTS WHICH STILL NEED TO BE OVERHAULED BELOW: ***/
	
	
	/**
	 * Checks if getNodeByUUID returns a Node object on an existing node.
	 * @test
	 */
	public function getNodeByExistingUUIDReturnsANode() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';
		$node = $this->session->getNodeByUUID($uuid);
		$this->assertType('T3_phpCR_NodeInterface', $node, 'The session did not return a node object on getNodeByUUID('.$uuid.').');
	}
	
	/**
	 * Checks if getNodeByUUID fails properly on a non-existing node.
	 * @test
	 */
	public function getNodeByNotExistingUUIDFails() {
		try {
			$uuid = 'hurzhurz-hurz-hurz-hurz-hurzhurzhurz';
			$node = $this->session->getNodeByUUID($uuid);
			$this->fail('getNodeByUUID with a non-exsting UUID must throw a T3_phpCR_ItemNotFoundException');
		} catch (T3_phpCR_ItemNotFoundException $e) {}
	}
	
	/**
	 * Checks of getNodeByUUID actually returns the requested node (determined through $node->getUUID()).
	 * @test
	 */
	public function getNodeByUUIDReturnsTheRequestedNode() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';
		$node = $this->session->getNodeByUUID($uuid);
		$this->assertEquals($uuid, $node->getUUID(), 'The returned node did not have the same UUID as requested.');
	}
	
	/**
	 * Checks if isLve() returns false after logout().
	 * @test
	 */
	public function isLiveReturnsFalseAfterLogout() {
		$this->session->logout();
		$this->assertEquals(FALSE, $this->session->isLive(), 'isLive did not return FALSE after logout() has been called.');
	}
	
	/**
	 * Checks if getItem works
	 * @test
	 */
	public function getItemWorks() {
		$expectedTitle = 'News about the TYPO3CR';
		$newsItem = $this->session->getItem('Content/Categories/Pages/Home/News/title');
		$this->assertEquals($expectedTitle, $newsItem->getString(), 'It did not return the property as expected.');
		
		$expectedTitle = 'News';
		$newsItem = $this->session->getItem('Content/Categories/Pages/Home/News/');
		$this->assertEquals($expectedTitle, $newsItem->getName(), 'It did not return the expected node title.');
	
		$testPropertyNode = $this->session->getRootNode()->addNode('TestPropertyNode');
		$this->assertSame($testPropertyNode, $this->session->getItem('/TestPropertyNode'), 'The returned TestPropertyNode was not the same as the local one.');
		$testPropertyNode->remove();
		$this->session->save();
	}
	
	/**
	 * Checks if getNode works
	 * @test
	 */
	public function getNodeWorks() {
		$expectedTitle = 'News';
		$newsItem = $this->session->getNode('Content/Categories/Pages/Home/News/');
		$this->assertEquals($expectedTitle, $newsItem->getName(), 'It did not return the node.');
	}
	
	/**
	 * Checks if getProperty works
	 * @test
	 */
	public function getPropertyWorks() {
		$expectedTitle = 'News about the TYPO3CR';
		$newsItem = $this->session->getItem('Content/Categories/Pages/Home/News/title');
		$this->assertEquals($expectedTitle, $newsItem->getString(), 'It did not return the property.');
	}
	
	/**
	 * Checks if getValueFactory() returns a ValueFactory
	 * @test
	 */
	public function getValueFactoryReturnsAValueFactory() {
		$this->assertType('T3_phpCR_ValueFactoryInterface', $this->session->getValueFactory(), 'The session did not return a ValueFactory object on getValueFactory().');
	}
	
	/**
	 * Checks the namespace mappings
	 * @test
	 */
	public function getNamespacePrefixesWorks() {
		$persistentPrefixes = $this->session->getWorkspace()->getNamespaceRegistry()->getPrefixes();
		$sessionPrefixes = $this->session->getNamespacePrefixes();
		$this->assertEquals($persistentPrefixes, $sessionPrefixes, 'getNamespacePrefixes() did not return all the persistent namespaces.');
	}
	
	/**
	 * Checks if fetching the URI for the jcr namespace prefix is as expected
	 * @test
	 */
	public function getNamespaceURIWorks() {
		$expectedURI = 'http://www.jcp.org/jcr/1.0'; 
		$returnedURI = $this->session->getNamespaceURI('jcr');
		$this->assertEquals($expectedURI, $returnedURI, 'The namespace URI for the prefix "jcr" was not successfully received. (Received: '.$returnedURI.')');
	}
	
	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @test
	 */
	public function setNamespacePrefixWorks() {
		try {
			$this->session->setNamespacePrefix('xMLtest', 'http://should.throw/exception');
			$this->fail('Prefix starts with XML, but does not throw an exception!');
		} catch (T3_phpCR_NamespaceException $e) {
		}
	
		try {
			$this->session->setNamespacePrefix('', 'http://should.throw/exception');
			$this->fail('Prefix is empty, but no exception is thrown!');
		} catch (T3_phpCR_NamespaceException $e) {
		}
	
		try {
			$this->session->setNamespacePrefix('testprefix', '');
			$this->fail('URI is empty, but no exception is thrown!');
		} catch (T3_phpCR_NamespaceException $e) {
		}
	
		$testUri = 'http://typo3.org/jcr/test';
		$this->session->setNamespacePrefix('localPrefixToTest', $testUri);
		$this->assertEquals($testUri, $this->session->getNamespaceUri('localPrefixToTest'), 'Prefix was not registered!');
	
		$this->session->setNamespacePrefix('nt', $testUri);
		$this->assertEquals($testUri, $this->session->getNamespaceUri('nt'), 'Reregistering an already existing prefix does not work.');
	}
}
?>