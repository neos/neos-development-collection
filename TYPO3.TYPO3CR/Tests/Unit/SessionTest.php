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
 * Tests for the Session implementation of TYPO3CR
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SessionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\PHPCR\RepositoryInterface
	 */
	protected $mockRepository;

	/**
	 * @var \F3\TYPO3CR\MockStorageBackend
	 */
	protected $mockStorageBackend;

	/**
	 * @var \F3\TYPO3CR\MockValueFactory
	 */
	protected $mockValueFactory;

	/**
	 * @var \F3\TYPO3CR\Session
	 */
	protected $session;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
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
				'96bca35d-1ef5-4a47-8b0c-0dbd68507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0dbd68507d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 'nt:base',
					'name' => 'jcr:xmltext'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d10' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 0,
					'name' => 'Content'
				),
				'96bca35d-1ef5-4a47-8b0c-0dbd65507d00' => array(
					'identifier' => '96bca35d-1ef5-4a47-8b0c-0dbd65507d00',
					'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 'nt:base',
					'name' => 'jcr:xmltext'
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
						'value' => 'News about FLOW3 & the TYPO3CR',
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
						'type' => \F3\PHPCR\PropertyType::WEAKREFERENCE
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
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d10' => array(
					array(
						'name' => 'binaryProperty',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
						'value' => 'a345öčřßa',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::BINARY
					)
				),
				'96bca35d-1ef5-4a47-8b0c-0dbd68507d00' => array(
					array(
						'name' => 'jcr:xmlcharacters',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0dbd68507d00',
						'value' => 'This is some XML text containing <weird> "stuff"',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::STRING
					)
				),
				'96bca35d-1ef5-4a47-8b0c-0dbd65507d00' => array(
					array(
						'name' => 'jcr:xmlcharacters',
						'parent' => '96bca35d-1ef5-4a47-8b0c-0dbd65507d00',
						'value' => 'Another XML text node property',
						'namespace' => '',
						'multivalue' => FALSE,
						'type' => \F3\PHPCR\PropertyType::STRING
					)
				)
			)
		);

		$this->session = new \F3\TYPO3CR\Session('default', $this->mockRepository, $this->mockStorageBackend, $this->objectFactory);
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
		$this->assertType('F3\PHPCR\WorkspaceInterface', $this->session->getWorkspace(), 'The session did not return a workspace object on getWorkspace().');
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
		$this->assertType('F3\PHPCR\NodeInterface', $node, 'The session did not return a node object on getNodeByIdentifier(' . $identifier . ').');
		$this->assertEquals($identifier, $node->getIdentifier(), 'The session did not return the expected node object on getNodeByIdentifier(' . $identifier . ').');
	}

	/**
	 * Checks if getNodeByIdentifier fails properly on a non-existing node.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\ItemNotFoundException
	 */
	public function getNodeByNotExistingIdentifierFails() {
		$identifier = 'hurzhurz-hurz-hurz-hurz-hurzhurzhurz';
		$this->session->getNodeByIdentifier($identifier);
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
		$expectedTitle = 'News about FLOW3 & the TYPO3CR';
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
		$testPropertyNode = $this->session->getRootNode()->addNode('TestPropertyNode', 'nt:base');
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
		$this->assertEquals('News about FLOW3 & the TYPO3CR', $property->getString(), 'getProperty() did not return the property.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueFactoryReturnsAValueFactory() {
		$this->assertType('F3\PHPCR\ValueFactoryInterface', $this->session->getValueFactory(), 'The session did not return a ValueFactory object on getValueFactory().');
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function setNamespacePrefixRejectsXML() {
		$this->session->setNamespacePrefix('xMLtest', 'http://should.throw/exception');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function setNamespacePrefixRejectsEmptyPrefix() {
		$this->session->setNamespacePrefix('', 'http://should.throw/exception');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function setNamespacePrefixRejectsEmptyURI() {
		$this->session->setNamespacePrefix('testprefix', '');
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function getNamespaceUriThrowsExceptionIfPrefixIsUnknown() {
		$this->session->getNamespaceUri('someNonExistingPrefix');
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesNewNodes() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('addNode');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array('refresh'), array('default', $this->mockRepository, $mockStorageBackend, $this->objectFactory));
		$node = new \F3\TYPO3CR\Node(array('identifier' => '123', 'nodetype' => 'nt:base'), $mockSession, $this->objectFactory);

		$mockSession->registerNodeAsNew($node);
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesDirtyNodes() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('updateNode');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array('refresh'), array('default', $this->mockRepository, $mockStorageBackend, $this->objectFactory));
		$node = new \F3\TYPO3CR\Node(array('identifier' => '123', 'nodetype' => 'nt:base'), $mockSession, $this->objectFactory);

		$mockSession->registerNodeAsDirty($node);
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesRemovedNodes() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('removeNode');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array('refresh'), array('default', $this->mockRepository, $mockStorageBackend, $this->objectFactory));
		$node = new \F3\TYPO3CR\Node(array('identifier' => '123', 'nodetype' => 'nt:base'), $mockSession, $this->objectFactory);

		$mockSession->registerNodeAsRemoved($node);
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesNewProperties() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('addProperty');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array('refresh'), array('default', $this->mockRepository, $mockStorageBackend, $this->objectFactory));
		$node = new \F3\TYPO3CR\Node(array('identifier' => '123', 'nodetype' => 'nt:base'), $mockSession, $this->objectFactory);

		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$property = new \F3\TYPO3CR\Property('someProp', 'someValue', \F3\PHPCR\PropertyType::STRING, $node, $mockSession, $mockValueFactory);

		$mockSession->registerNodeAsDirty($node);
		$mockSession->registerPropertyAsNew($property);
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesDirtyProperties() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('updateProperty');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array('refresh'), array('default', $this->mockRepository, $mockStorageBackend, $this->objectFactory));
		$node = new \F3\TYPO3CR\Node(array('identifier' => '123', 'nodetype' => 'nt:base'), $mockSession, $this->objectFactory);

		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$property = new \F3\TYPO3CR\Property('someProp', 'someValue', \F3\PHPCR\PropertyType::STRING, $node, $mockSession, $mockValueFactory);

		$mockSession->registerNodeAsDirty($node);
		$mockSession->registerPropertyAsDirty($property);
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesRemovedProperties() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('removeProperty');
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array('refresh'), array('default', $this->mockRepository, $mockStorageBackend, $this->objectFactory));
		$node = new \F3\TYPO3CR\Node(array('identifier' => '123', 'nodetype' => 'nt:base'), $mockSession, $this->objectFactory);

		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$property = new \F3\TYPO3CR\Property('someProp', 'someValue', \F3\PHPCR\PropertyType::STRING, $node, $mockSession, $mockValueFactory);

		$mockSession->registerNodeAsDirty($node);
		$mockSession->registerPropertyAsRemoved($property);
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\ReferentialIntegrityException
	 */
	public function removeOnAReferenceTargetThrowsExceptionOnSave() {
			// /Content/News is target of the REFERENCE /Content/RefParent/RefSource/ref
		$this->session->getRootNode()->getNode('Content/News')->remove();
		$this->session->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function canRemoveASubtreeContainingExternalReference() {
			// /Content/News is target of the REFERENCE /Content/ExternalRefParent/RefSource/ref
		$this->session->getRootNode()->getNode('Content/ExternalRefParent')->remove();
		$this->session->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function canRemoveASubtreeContainingInternalReference() {
			// /Content/InternalRefParent/RefTarget is target of the REFERENCE /Content/InternalRefParent/RefSource/ref
		$this->session->getRootNode()->getNode('Content/InternalRefParent')->remove();
		$this->session->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewDeclaresNamespaces() {
		$expectedNamespaces = array(
			'sv' => 'http://www.jcp.org/jcr/sv/1.0',
			'jcr' => 'http://www.jcp.org/jcr/1.0',
			'nt' => 'http://www.jcp.org/jcr/nt/1.0',
			'mix' => 'http://www.jcp.org/jcr/mix/1.0'
		);

		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertSame($expectedNamespaces, $xml->getDocNamespaces());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsRootNodeNamedAsJcrRoot() {
		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('jcr:root', (string)$xml->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsRequestedPath() {
		$this->session->exportSystemView('/Content/News', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('News', (string)$xml->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsRecursivelyIfRequested() {
		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, FALSE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals(2, count($xml->children('http://www.jcp.org/jcr/sv/1.0')->node));
		$this->assertEquals(4, count($xml->children('http://www.jcp.org/jcr/sv/1.0')->node[1]->node));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsNonRecursivelyIfRequested() {
		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals(0, count($xml->children('http://www.jcp.org/jcr/sv/1.0')->node));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsPrimaryNodeTypeAsFirstPropertyNamedJcrPrimaryType() {
		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('jcr:primaryType', (string)$xml->children('http://www.jcp.org/jcr/sv/1.0')->property[0]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsIdentifierAsThirdPropertyNamedJcrUuid() {
		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('jcr:uuid', (string)$xml->children('http://www.jcp.org/jcr/sv/1.0')->property[2]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
		$this->assertEquals('96bca35d-1ef5-4a47-8b0c-0ddd69507d00', (string)$xml->children('http://www.jcp.org/jcr/sv/1.0')->property[2]->value[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsProperties() {
		$this->session->exportSystemView('/', 'memory://typo3crexporttestdata', TRUE, FALSE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$children = $xml->children('http://www.jcp.org/jcr/sv/1.0');
		$this->assertEquals('title', (string)$children->node[1]->node[1]->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
		$this->assertEquals('String', (string)$children->node[1]->node[1]->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->type);
		$this->assertEquals('News about FLOW3 & the TYPO3CR', (string)$children->node[1]->node[1]->property[3]->value[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsBinaryPropertyAsBase64() {
		$this->session->exportSystemView('/Content', 'memory://typo3crexporttestdata', FALSE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$children = $xml->children('http://www.jcp.org/jcr/sv/1.0');
		$this->assertEquals('binaryProperty', (string)$children->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
		$this->assertEquals('Binary', (string)$children->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->type);
		$this->assertEquals('YTM0NcO2xI3FmcOfYQ==', (string)$children->property[3]->value[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewSkipsBinaryPropertyIfRequested() {
		$this->session->exportSystemView('/Content', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$children = $xml->children('http://www.jcp.org/jcr/sv/1.0');
		$this->assertEquals('binaryProperty', (string)$children->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
		$this->assertEquals('Binary', (string)$children->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->type);
		$this->assertEquals('', (string)$children->property[3]->value[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewDeclaresNamespaces() {
		$expectedNamespaces = array(
			'jcr' => 'http://www.jcp.org/jcr/1.0',
			'nt' => 'http://www.jcp.org/jcr/nt/1.0',
			'mix' => 'http://www.jcp.org/jcr/mix/1.0'
		);

		$this->session->exportDocumentView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertSame($expectedNamespaces, $xml->getDocNamespaces());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsRootNodeAsJcrRootElement() {
		$this->session->exportDocumentView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('root', $xml->getName());
		$this->assertTrue(array_key_exists('jcr', $xml->getNamespaces(FALSE)));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsRequestedPath() {
		$this->session->exportDocumentView('/Content/News', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('News', $xml->getName());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsRecursivelyIfRequested() {
		$this->session->exportDocumentView('/', 'memory://typo3crexporttestdata', TRUE, FALSE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals(1, count($xml->children()));
		$this->assertEquals(3, count($xml->Content->children()));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsNonRecursivelyIfRequested() {
		$this->session->exportDocumentView('/', 'memory://typo3crexporttestdata', TRUE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals(0, count($xml->children()));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsProperties() {
		$this->session->exportDocumentView('/', 'memory://typo3crexporttestdata', TRUE, FALSE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('News about FLOW3 & the TYPO3CR', (string)$xml->Content[0]->News[0]->attributes()->title);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsBinaryPropertyAsBase64() {
		$this->session->exportDocumentView('/Content', 'memory://typo3crexporttestdata', FALSE, TRUE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('YTM0NcO2xI3FmcOfYQ==', (string)$xml->attributes()->binaryProperty);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsXMLTextNodesAsXMLText() {
		$this->session->exportDocumentView('/', 'memory://typo3crexporttestdata', TRUE, FALSE);

		$xml = new \SimpleXMLElement(file_get_contents('memory://typo3crexporttestdata'));
		$this->assertEquals('This is some XML text containing <weird> "stuff"', (string)$xml);
		$this->assertEquals('Another XML text node property', (string)$xml->Content);
	}

}
?>