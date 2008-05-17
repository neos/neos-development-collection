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

require_once('Fixtures/F3_TYPO3CR_MockStorageAccess.php');

/**
 * Tests for the Session implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_SessionTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if getRepository returns the Repository object used to create the Session object.
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getRepositoryReturnsTheCreatingRepository() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$this->assertSame($mockRepository, $session->getRepository(), 'The session did not return the repository from which it was created.');
	}

	/**
	 * Checks if getWorkspace returns a Workspace object.
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getWorkspaceAlwaysReturnsTheAssociatedWorkspace() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		$this->assertType('F3_phpCR_WorkspaceInterface', $session->getWorkspace(), 'The session did not return a workspace object on getWorkspace().');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getRootNodeReturnsRootModeOfDefaultWorkspace() {
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

		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$rootNode = $session->getRootNode();

		$this->assertEquals('96bca35d-1ef5-4a47-8b0c-0ddd69507d00', $rootNode->getUUID(), 'The UUID of the retrieved root node is not as expected.');
	}

	/**
	 * Checks if getNodeByUUID returns a Node object on an existing node.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByExistingUUIDReturnsANode() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';

		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawNodesByUUIDGroupedByWorkspace = array(
			'default' => array(
				$uuid => array(
					'uuid' => $uuid,
					'pid' => 0,
					'nodetype' => 0,
					'name' => ''
				)
			)
		);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		$node = $session->getNodeByUUID($uuid);
		$this->assertType('F3_phpCR_NodeInterface', $node, 'The session did not return a node object on getNodeByUUID(' . $uuid . ').');
	}

	/**
	 * Checks if getNodeByUUID fails properly on a non-existing node.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByNotExistingUUIDFails() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		try {
			$uuid = 'hurzhurz-hurz-hurz-hurz-hurzhurzhurz';
			$node = $session->getNodeByUUID($uuid);
			$this->fail('getNodeByUUID with a non-exsting UUID must throw a F3_phpCR_ItemNotFoundException');
		} catch (F3_phpCR_ItemNotFoundException $e) {}
	}

	/**
	 * Checks of getNodeByUUID actually returns the requested node (determined through $node->getUUID()).
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByUUIDReturnsTheRequestedNode() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';

		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawNodesByUUIDGroupedByWorkspace = array(
			'default' => array(
				$uuid => array(
					'uuid' => $uuid,
					'pid' => 0,
					'nodetype' => 0,
					'name' => ''
				)
			)
		);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$node = $session->getNodeByUUID($uuid);
		$this->assertEquals($uuid, $node->getUUID(), 'The returned node did not have the same UUID as requested.');
	}

	/**
	 * Checks if isLve() returns FALSE after logout().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function isLiveReturnsFalseAfterLogout() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccess_PDO', array(), array(), '', FALSE);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$session->logout();
		$this->assertEquals(FALSE, $session->isLive(), 'isLive did not return FALSE after logout() has been called.');
	}

	/**
	 * Checks if getItem returns the expected items
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getItemReturnsTheExpectedItems() {
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

		$expectedTitle = 'News about the TYPO3CR';
		$newsItem = $session->getItem('News/title');
		$this->assertEquals($expectedTitle, $newsItem->getString(), 'getItem() did not return the property as expected.');

		$expectedTitle = 'News';
		$newsItem = $session->getItem('News/');
		$this->assertEquals($expectedTitle, $newsItem->getName(), 'getItem() did not return the expected node title.');
	}

	/**
	 * Checks if getItem returns the same node as just created
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getItemReturnsSameNodeAsAdded() {
		throw new PHPUnit_Framework_IncompleteTestError('Test for fetching a freshly added node not implemented yet.', 1211051320);

		$testPropertyNode = $this->session->getRootNode()->addNode('TestPropertyNode');
		$this->assertSame($testPropertyNode, $this->session->getItem('/TestPropertyNode'), 'The returned TestPropertyNode was not the same as the local one.');
		$testPropertyNode->remove();
		$this->session->save();
	}

	/**
	 * Checks if getNode works
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeReturnsTheExpectedNode() {
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
					'name' => 'News'
				),
			)
		);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		$newsItem = $session->getNode('News');
		$this->assertEquals('News', $newsItem->getName(), 'It did not return the node.');
	}

	/**
	 * Checks if getProperty works
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertyReturnsTheExpectedProperty() {
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

		$property = $session->getProperty('News/title');
		$this->assertEquals('News about the TYPO3CR', $property->getString(), 'getProperty() did not return the property.');
	}

	/**
	 * Checks if getValueFactory() returns a ValueFactory
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueFactoryReturnsAValueFactory() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccess_PDO', array(), array(), '', FALSE);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$this->assertType('F3_phpCR_ValueFactoryInterface', $session->getValueFactory(), 'The session did not return a ValueFactory object on getValueFactory().');
	}

	/**
	 * Checks the namespace mappings
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNamespacePrefixesReturnsTheSameResultAsTheWorkspaceNamespaceRegistry() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccess_PDO', array(), array(), '', FALSE);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		$persistentPrefixes = $session->getWorkspace()->getNamespaceRegistry()->getPrefixes();
		$sessionPrefixes = $session->getNamespacePrefixes();
		$this->assertEquals($persistentPrefixes, $sessionPrefixes, 'getNamespacePrefixes() did not return all the persistent namespaces.');
	}

	/**
	 * Checks if fetching the URI for the jcr namespace prefix is as expected
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNamespaceURIReturnsCorrectURIForJCRPrefix() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccess_PDO', array(), array(), '', FALSE);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		$expectedURI = 'http://www.jcp.org/jcr/1.0';
		$returnedURI = $session->getNamespaceURI('jcr');
		$this->assertEquals($expectedURI, $returnedURI, 'The namespace URI for the prefix "jcr" was not successfully received. (Received: ' . $returnedURI . ')');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccess_PDO', array(), array(), '', FALSE);
		$session = new F3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);

		try {
			$session->setNamespacePrefix('xMLtest', 'http://should.throw/exception');
			$this->fail('Prefix starts with XML, but does not throw an exception!');
		} catch (F3_phpCR_NamespaceException $e) {
		}

		try {
			$session->setNamespacePrefix('', 'http://should.throw/exception');
			$this->fail('Prefix is empty, but no exception is thrown!');
		} catch (F3_phpCR_NamespaceException $e) {
		}

		try {
			$session->setNamespacePrefix('testprefix', '');
			$this->fail('URI is empty, but no exception is thrown!');
		} catch (F3_phpCR_NamespaceException $e) {
		}

		try {
			$session->getNamespaceUri('someNonExistingPrefix');
			$this->fail('Unknown URI does not trigger exception.');
		} catch (F3_phpCR_NamespaceException $e) {}

		$testUri = 'http://typo3.org/jcr/test';
		$session->setNamespacePrefix('localPrefixToTest', $testUri);
		$this->assertEquals($testUri, $session->getNamespaceUri('localPrefixToTest'), 'Prefix was not registered!');

		$session->setNamespacePrefix('nt', $testUri);
		$this->assertEquals($testUri, $session->getNamespaceUri('nt'), 'Reregistering an already existing prefix does not work.');
		if (in_array('localPrefixToTest', $session->getNamespacePrefixes())) {
			$this->fail('Reregistering an already existing uri does not remove the existing prefix.');
		}
	}
}
?>