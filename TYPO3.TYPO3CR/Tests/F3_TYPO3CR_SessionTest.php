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
	 * @var F3_PHPCR_RepositoryInterface
	 */
	protected $mockRepository;

	/**
	 * @var F3_TYPO3CR_MockStorageAccess
	 */
	protected $mockStorageAccess;

	/**
	 * @var F3_TYPO3CR_Session
	 */
	protected $session;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->mockRepository = $this->getMock('F3_PHPCR_RepositoryInterface');
		$this->mockStorageAccess = new F3_TYPO3CR_MockStorageAccess();
		$this->mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'parent' => 0,
				'nodetype' => 'nt:base',
				'name' => ''
			)
		);
		$this->mockStorageAccess->rawNodesByIdentifierGroupedByWorkspace = array(
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
			)
		);
		$this->mockStorageAccess->rawPropertiesByIdentifierGroupedByWorkspace = array(
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

		$this->session = new F3_TYPO3CR_Session('default', $this->mockRepository, $this->mockStorageAccess, $this->componentManager);
	}

	/**
	 * Checks if getRepository returns the Repository object used to create the Session object.
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getRepositoryReturnsTheCreatingRepository() {
		$this->assertSame($this->mockRepository, $this->session->getRepository(), 'The session did not return the repository from which it was created.');
	}

	/**
	 * Checks if getWorkspace returns a Workspace object.
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getWorkspaceReturnsTheAssociatedWorkspace() {
		$this->assertType('F3_PHPCR_WorkspaceInterface', $this->session->getWorkspace(), 'The session did not return a workspace object on getWorkspace().');
		$this->assertEquals('default', $this->session->getWorkspace()->getName(), 'The session did not return the expected workspace object on getWorkspace().');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function getRootNodeReturnsRootNodeOfDefaultWorkspace() {
		$rootNode = $this->session->getRootNode();
		$this->assertEquals('96bca35d-1ef5-4a47-8b0c-0ddd69507d00', $rootNode->getIdentifier(), 'The Identifier of the retrieved root node is not as expected.');
	}

	/**
	 * Checks if getNodeByIdentifier returns a Node object on an existing node.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByExistingIdentifierReturnsANode() {
		$identifier = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$node = $this->session->getNodeByIdentifier($identifier);
		$this->assertType('F3_PHPCR_NodeInterface', $node, 'The session did not return a node object on getNodeByIdentifier(' . $identifier . ').');
		$this->assertEquals($identifier, $node->getIdentifier(), 'The session did not return the expected node object on getNodeByIdentifier(' . $identifier . ').');
	}

	/**
	 * Checks if getNodeByIdentifier fails properly on a non-existing node.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByNotExistingIdentifierFails() {
		try {
			$identifier = 'hurzhurz-hurz-hurz-hurz-hurzhurzhurz';
			$this->session->getNodeByIdentifier($identifier);
			$this->fail('getNodeByIdentifier with a non-exsting Identifier must throw a F3_PHPCR_ItemNotFoundException');
		} catch (F3_PHPCR_ItemNotFoundException $e) {}
	}

	/**
	 * Checks of getNodeByIdentifier actually returns the requested node (determined through $node->getIdentifier()).
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByIdentifierReturnsTheRequestedNode() {
		$identifier = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$node = $this->session->getNodeByIdentifier($identifier);
		$this->assertEquals($identifier, $node->getIdentifier(), 'The returned node did not have the same Identifier as requested.');
	}

	/**
	 * Checks if isLve() returns FALSE after logout().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function isLiveReturnsFalseAfterLogout() {
		$this->session->logout();
		$this->assertEquals(FALSE, $this->session->isLive(), 'isLive did not return FALSE after logout() has been called.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getItemReturnsTheExpectedItems() {
		$expectedTitle = 'News about the TYPO3CR';
		$newsItem = $this->session->getItem('Content/News/title');
		$this->assertEquals($expectedTitle, $newsItem->getString(), 'getItem() did not return the property as expected.');

		$expectedTitle = 'News';
		$newsItem = $this->session->getItem('Content/News/');
		$this->assertEquals($expectedTitle, $newsItem->getName(), 'getItem() did not return the expected node title.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getItemReturnsSameNodeAsAdded() {
		$testPropertyNode = $this->session->getRootNode()->addNode('TestPropertyNode');
		$this->assertSame($testPropertyNode, $this->session->getItem('/TestPropertyNode'), 'The returned TestPropertyNode was not the same as the local one.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeReturnsTheExpectedNode() {
		$newsItem = $this->session->getNode('Content/News');
		$this->assertEquals('News', $newsItem->getName(), 'It did not return the node.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertyReturnsTheExpectedProperty() {
		$property = $this->session->getProperty('Content/News/title');
		$this->assertEquals('News about the TYPO3CR', $property->getString(), 'getProperty() did not return the property.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueFactoryReturnsAValueFactory() {
		$this->assertType('F3_PHPCR_ValueFactoryInterface', $this->session->getValueFactory(), 'The session did not return a ValueFactory object on getValueFactory().');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNamespacePrefixesReturnsTheSameResultAsTheWorkspaceNamespaceRegistry() {
		$persistentPrefixes = $this->session->getWorkspace()->getNamespaceRegistry()->getPrefixes();
		$sessionPrefixes = $this->session->getNamespacePrefixes();
		$this->assertEquals($persistentPrefixes, $sessionPrefixes, 'getNamespacePrefixes() did not return all the persistent namespaces.');
	}

	/**
	 * Checks if fetching the URI for the jcr namespace prefix is as expected
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNamespaceURIReturnsCorrectURIForJCRPrefix() {
		$expectedURI = 'http://www.jcp.org/jcr/1.0';
		$returnedURI = $this->session->getNamespaceURI('jcr');
		$this->assertEquals($expectedURI, $returnedURI, 'The namespace URI for the prefix "jcr" was not successfully received. (Received: ' . $returnedURI . ')');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixRejectsXML() {
		try {
			$this->session->setNamespacePrefix('xMLtest', 'http://should.throw/exception');
			$this->fail('Prefix starts with XML, but does not throw an exception!');
		} catch (F3_PHPCR_NamespaceException $e) {
		}
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixRejectsEmptyPrefix() {
		try {
			$this->session->setNamespacePrefix('', 'http://should.throw/exception');
			$this->fail('Prefix is empty, but no exception is thrown!');
		} catch (F3_PHPCR_NamespaceException $e) {
		}
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixRejectsEmptyURI() {
		try {
			$this->session->setNamespacePrefix('testprefix', '');
			$this->fail('URI is empty, but no exception is thrown!');
		} catch (F3_PHPCR_NamespaceException $e) {
		}
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNamespaceUriThrowsExceptionIfPrefixIsUnknown() {
		try {
			$this->session->getNamespaceUri('someNonExistingPrefix');
			$this->fail('Unknown URI does not trigger exception.');
		} catch (F3_PHPCR_NamespaceException $e) {
		}
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixRegistersNamespace() {
		$testUri = 'http://typo3.org/jcr/test';
		$this->session->setNamespacePrefix('localPrefixToTest', $testUri);
		$this->assertEquals($testUri, $this->session->getNamespaceUri('localPrefixToTest'), 'Prefix was not registered!');

		$this->session->setNamespacePrefix('nt', $testUri);
		$this->assertEquals($testUri, $this->session->getNamespaceUri('nt'), 'Reregistering an already existing prefix does not work.');
		if (in_array('localPrefixToTest', $this->session->getNamespacePrefixes())) {
			$this->fail('Reregistering an already existing uri does not remove the existing prefix.');
		}
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixReregistersNamespaceForExistingPrefix() {
		$testUri = 'http://typo3.org/jcr/test';
		$this->session->setNamespacePrefix('localPrefixToTest', $testUri);
		$this->session->setNamespacePrefix('nt', $testUri);
		$this->assertEquals($testUri, $this->session->getNamespaceUri('nt'), 'Reregistering an already existing prefix does not work.');
		if (in_array('localPrefixToTest', $this->session->getNamespacePrefixes())) {
			$this->fail('Reregistering an already existing uri does not remove the existing prefix.');
		}
	}

}
?>