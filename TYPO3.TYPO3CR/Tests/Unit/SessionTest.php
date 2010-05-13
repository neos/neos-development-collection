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

/**
 * Tests for the Session implementation of TYPO3CR
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SessionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getRepositoryReturnsTheCreatingRepository() {
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$session = new \F3\TYPO3CR\Session('default', $mockRepository, $this->getMock('F3\TYPO3CR\Storage\BackendInterface'), $this->getMock('F3\FLOW3\Object\ObjectManagerInterface'));
		$this->assertSame($mockRepository, $session->getRepository(), 'The session did not return the repository from which it was created.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getWorkspaceReturnsTheAssociatedWorkspace() {
		$mockWorkspace= $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('create')->with('F3\PHPCR\WorkspaceInterface', 'default')->will($this->returnValue($mockWorkspace));
		$session = new \F3\TYPO3CR\Session('default', $this->getMock('F3\PHPCR\RepositoryInterface'), $this->getMock('F3\TYPO3CR\Storage\BackendInterface'), $mockObjectManager);

		$this->assertSame($mockWorkspace, $session->getWorkspace());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueFactoryReturnsAValueFactory() {
		$mockValueFactory= $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(1))->method('create')->with('F3\PHPCR\ValueFactoryInterface')->will($this->returnValue($mockValueFactory));
		$session = new \F3\TYPO3CR\Session('default', $this->getMock('F3\PHPCR\RepositoryInterface'), $this->getMock('F3\TYPO3CR\Storage\BackendInterface'), $mockObjectManager);

		$this->assertSame($mockValueFactory, $session->getValueFactory());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getRootNodeFetchesRootNodeIfNotYetFetched() {
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('getIdentifier')->will($this->returnValue('rootUuid'));
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('getRawRootNode')->will($this->returnValue('would-be-raw-rootnode'));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeInterface', 'would-be-raw-rootnode')->will($this->returnValue($mockRootNode));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('objectManager', $mockObjectManager);
		$session->_set('storageBackend', $mockStorageBackend);

		$session->getRootNode();
	}

	/**
	 * Checks if getNodeByIdentifier returns a Node object on an existing node.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByIdentifierReturnsExistingNode() {
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('currentlyLoadedNodes', array('knownUuid' => 'would-be-known-node'));

		$this->assertEquals('would-be-known-node', $session->getNodeByIdentifier('knownUuid'));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\ItemNotFoundException
	 */
	public function getNodeByIdentifierThrowsExceptionOnUnknownIdentifier() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('getRawNodeByIdentifier')->will($this->returnValue(FALSE));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('storageBackend', $mockStorageBackend);

		$session->getNodeByIdentifier('unknownUuid');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeByIdentifierReturnsTheRequestedNode() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->once())->method('getIdentifier')->will($this->returnValue('knownUuid'));
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('getRawNodeByIdentifier')->will($this->returnValue('would-be-raw-node'));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeInterface', 'would-be-raw-node')->will($this->returnValue($node));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('objectManager', $mockObjectManager);
		$session->_set('storageBackend', $mockStorageBackend);

		$this->assertSame($node, $session->getNodeByIdentifier('knownUuid'));
	}

	/**
	 * Checks if isLve() returns FALSE after logout().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function isLiveReturnsFalseAfterLogout() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('disconnect');
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('storageBackend', $mockStorageBackend);
		$session->logout();
		$this->assertEquals(FALSE, $session->isLive(), 'isLive did not return FALSE after logout() has been called.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getItemFetchesTheExpectedItem() {
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('rootNode', $rootNode);

		$this->assertSame($rootNode, $session->getItem('/'));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeFetchesTheExpectedNode() {
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$rootNode->expects($this->once())->method('getNode')->with('Content/News');
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('rootNode', $rootNode);

		$session->getNode('Content/News');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertyFetchesTheExpectedProperty() {
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$rootNode->expects($this->once())->method('getProperty')->with('Content/News/title');
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('rootNode', $rootNode);

		$session->getProperty('Content/News/title');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function setNamespacePrefixRejectsXML() {
		$session = $this->getMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->setNamespacePrefix('xMLtest', 'http://should.throw/exception');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function setNamespacePrefixRejectsEmptyPrefix() {
		$session = $this->getMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->setNamespacePrefix('', 'http://should.throw/exception');
	}

	/**
	 * Checks if setNameSpacePrefix follows the rules of the specification
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NamespaceException
	 */
	public function setNamespacePrefixRejectsEmptyUri() {
		$session = $this->getMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->setNamespacePrefix('testprefix', '');
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixRegistersNamespace() {
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$testUri = 'http://typo3.org/jcr/test';
		$session->setNamespacePrefix('localPrefixToTest', $testUri);
		$this->assertSame(array('localPrefixToTest' => $testUri), $session->_get('localNamespaceMappings'));
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setNamespacePrefixRemovesExistingPrefix() {
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$testUri = 'http://typo3.org/jcr/test';
		$session->_set('localNamespaceMappings', array('existingPrefix' => $testUri));

		$session->setNamespacePrefix('localPrefixToTest', $testUri);
		$this->assertSame(array('localPrefixToTest' => $testUri), $session->_get('localNamespaceMappings'));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesNewNodes() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('addNode')->with($node);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockSession = $this->getAccessibleMock('F3\TYPO3CR\Session', array('addPropertiesForNode'), array('default', $mockRepository, $mockStorageBackend, $mockObjectManager));
		$mockSession->expects($this->once())->method('addPropertiesForNode')->with($node);
		$mockSession->_set('currentlyNewNodes', array('fakeUuid' => $node));

		$mockSession->save();
			// call again, must not find anymore new nodes
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesDirtyNodes() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('updateNode')->with($node);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockSession = $this->getAccessibleMock('F3\TYPO3CR\Session', array('addPropertiesForNode', 'updatePropertiesForNode', 'removePropertiesForNode'), array('default', $mockRepository, $mockStorageBackend, $mockObjectManager));
		$mockSession->expects($this->once())->method('addPropertiesForNode')->with($node);
		$mockSession->expects($this->once())->method('updatePropertiesForNode')->with($node);
		$mockSession->expects($this->once())->method('removePropertiesForNode')->with($node);
		$mockSession->_set('currentlyDirtyNodes', array('fakeUuid' => $node));

		$mockSession->save();
			// call again, must not find anymore dirty nodes
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveProcessesRemovedNodes() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('removeNode')->with($node);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockSession = $this->getAccessibleMock('F3\TYPO3CR\Session', array('removePropertiesForNode'), array('default', $mockRepository, $mockStorageBackend, $mockObjectManager));
		$mockSession->expects($this->once())->method('removePropertiesForNode')->with($node);
		$mockSession->_set('currentlyRemovedNodes', array('fakeUuid' => $node));

		$mockSession->save();
			// call again, must not find anymore removed nodes
		$mockSession->save();
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addPropertiesForNodeProcessesProperties() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->any())->method('getName')->will($this->returnValue('someProperty'));
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('addProperty')->with($property);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockSession = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array('default', $mockRepository, $mockStorageBackend, $mockObjectManager));
		$mockSession->_set('currentlyNewProperties', array('fakeUuid' => array('someProperty' => $property)));

		$mockSession->_call('addPropertiesForNode', $node);
			// call again, must not find anymore new properties
		$mockSession->_call('addPropertiesForNode', $node);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updatePropertiesForNodeProcessesProperties() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->any())->method('getName')->will($this->returnValue('someProperty'));
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('updateProperty')->with($property);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockSession = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array('default', $mockRepository, $mockStorageBackend, $mockObjectManager));
		$mockSession->_set('currentlyDirtyProperties', array('fakeUuid' => array('someProperty' => $property)));

		$mockSession->_call('updatePropertiesForNode', $node);
			// call again, must not find anymore dirty properties
		$mockSession->_call('updatePropertiesForNode', $node);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removePropertiesForNodeProcessesProperties() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->any())->method('getName')->will($this->returnValue('someProperty'));
		$mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('removeProperty')->with($property);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockSession = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array('default', $mockRepository, $mockStorageBackend, $mockObjectManager));
		$mockSession->_set('currentlyRemovedProperties', array('fakeUuid' => array('someProperty' => $property)));

		$mockSession->_call('removePropertiesForNode', $node);
			// call again, must not find anymore dirty properties
		$mockSession->_call('removePropertiesForNode', $node);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function saveValidatesPendingChanges() {
		$session = $this->getMock('F3\TYPO3CR\Session', array('validatePendingChanges'), array(), '', FALSE);
		$session->expects($this->once())->method('validatePendingChanges')->will($this->throwException(new \Exception()));

		$session->save();

	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validatePendingChangesChecksRemovedNodesForBeingReferenced() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('isReferenceTarget')->with('removedUuid')->will($this->returnValue(FALSE));

		$removedNode = $this->getMock('F3\PHPCR\NodeInterface');
		$removedNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('removedUuid'));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('storageBackend', $mockStorageBackend);
		$session->_set('currentlyRemovedNodes', array('removedUuid' => $removedNode));

		$session->_call('validatePendingChanges');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validatePendingChangesAllowsRemovedOfReferencedNodesIfReferencingNodeWillBeDeletedAsWell() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('isReferenceTarget')->with('removedUuid')->will($this->returnValue(TRUE));

		$removedNode = $this->getMock('F3\PHPCR\NodeInterface');

		$referencingNode = $this->getMock('F3\PHPCR\NodeInterface');
		$referencingNode->expects($this->any())->method('getParent')->will($this->returnValue($removedNode));

		$removedNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('removedUuid'));
		$removedNode->expects($this->any())->method('getReferences')->will($this->returnValue(array($referencingNode)));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('storageBackend', $mockStorageBackend);
		$session->_set('currentlyRemovedNodes', array('removedUuid' => $removedNode));

		$session->_call('validatePendingChanges');
	}

	/**
	 * @test
	 * @expectedException \F3\PHPCR\ReferentialIntegrityException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validatePendingChangesThrowsExceptionOnFailure() {
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockStorageBackend->expects($this->once())->method('isReferenceTarget')->with('removedUuid')->will($this->returnValue(TRUE));

		$otherNode = $this->getMock('F3\PHPCR\NodeInterface');

		$referencingNode = $this->getMock('F3\PHPCR\NodeInterface');
		$referencingNode->expects($this->any())->method('getParent')->will($this->returnValue($otherNode));

		$removedNode = $this->getMock('F3\PHPCR\NodeInterface');
		$removedNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('removedUuid'));
		$removedNode->expects($this->any())->method('getReferences')->will($this->returnValue(array($referencingNode)));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('dummy'), array(), '', FALSE);
		$session->_set('storageBackend', $mockStorageBackend);
		$session->_set('currentlyRemovedNodes', array('removedUuid' => $removedNode));

		$session->_call('validatePendingChanges');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function exportToXMLForSystemViewDelegatesTheHardWork() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->once())->method('getDepth')->will($this->returnValue(0));
		$node->expects($this->once())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);
		$session->expects($this->once())->method('getNamespacePrefixes')->will($this->returnValue(array()));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_SYSTEM, TRUE);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function exportToXMLForDocumentViewDelegatesTheHardWork() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->once())->method('getDepth')->will($this->returnValue(0));
		$node->expects($this->once())->method('getProperties')->will($this->returnValue(array()));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);
		$session->expects($this->once())->method('getNamespacePrefixes')->will($this->returnValue(array()));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_DOCUMENT, TRUE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function writeNamespaceAttributesIteratesOverAllPrefixes() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNode', 'getNameSpacePrefixes', 'getNameSpaceURI'), array(), '', FALSE);
		$session->expects($this->once())->method('getNameSpacePrefixes')->will($this->returnValue(array('nt')));
		$session->expects($this->once())->method('getNameSpaceURI')->with('nt')->will($this->returnValue('http://www.jcp.org/jcr/nt/1.0'));

		$session->_call('writeNamespaceAttributes', $xmlWriter);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportToXMLForSystemViewExportsRootNodeNamedAsJcrRoot() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->once())->method('getDepth')->will($this->returnValue(0));
		$node->expects($this->once())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);
		$session->expects($this->once())->method('getNamespacePrefixes')->will($this->returnValue(array()));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_SYSTEM, TRUE);

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());
		$this->assertEquals('jcr:root', (string)$xml->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportToXMLForSystemViewExportsNodeName() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getDepth')->will($this->returnValue(1));
		$node->expects($this->any())->method('getName')->will($this->returnValue('NodeName'));
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);
		$session->expects($this->once())->method('getNamespacePrefixes')->will($this->returnValue(array()));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_SYSTEM, TRUE);

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());
		$this->assertEquals('NodeName', (string)$xml->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportToXMLForDocumentViewExportsRootNodeNamedAsJcrRoot() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->once())->method('getDepth')->will($this->returnValue(0));
		$node->expects($this->once())->method('getProperties')->will($this->returnValue(array()));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes', 'getNameSpaceURI'), array(), '', FALSE);
		$session->expects($this->once())->method('getNameSpacePrefixes')->will($this->returnValue(array('jcr')));
		$session->expects($this->once())->method('getNameSpaceURI')->with('jcr')->will($this->returnValue('http://www.jcp.org/jcr/1.0'));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_DOCUMENT, TRUE);

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());
		$this->assertEquals('root', $xml->getName());
		$this->assertTrue(array_key_exists('jcr', $xml->getNamespaces(FALSE)));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportToXMLForDocumentViewExportsNodeName() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getDepth')->will($this->returnValue(1));
		$node->expects($this->any())->method('getName')->will($this->returnValue('NodeName'));
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);
		$session->expects($this->once())->method('getNamespacePrefixes')->will($this->returnValue(array()));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_DOCUMENT, TRUE);

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());
		$this->assertEquals('NodeName', $xml->getName());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportToXMLExportsRecursivelyIfRequested() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');

		$subNode = $this->getMock('F3\PHPCR\NodeInterface');
		$subNode->expects($this->once())->method('getDepth')->will($this->returnValue(1));
		$subNode->expects($this->once())->method('getProperties')->will($this->returnValue(array()));
		$subNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$subNode->expects($this->once())->method('getNodes')->will($this->returnValue(array()));

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->once())->method('getDepth')->will($this->returnValue(0));
		$node->expects($this->once())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$node->expects($this->once())->method('getNodes')->will($this->returnValue(array($subNode)));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);
		$session->expects($this->once())->method('getNamespacePrefixes')->will($this->returnValue(array()));

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, TRUE, \F3\TYPO3CR\Session::EXPORT_SYSTEM, TRUE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsPrimaryNodeTypeAsFirstPropertyNamedJcrPrimaryType() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('jcr:root');
		$xmlWriter->writeAttribute('xmlns:jcr', 'http://www.jcp.org/jcr/1.0');
		$xmlWriter->writeAttribute('xmlns:sv', 'http://www.jcp.org/jcr/sv/1.0');

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);

		$session->_call('exportPropertiesForSystemView', $node, $xmlWriter, TRUE);
		$xmlWriter->endElement();
		$xmlWriter->endDocument();

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());

		$this->assertEquals('jcr:primaryType', (string)$xml->children('http://www.jcp.org/jcr/sv/1.0')->property[0]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsIdentifierAsThirdPropertyNamedJcrUuid() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('jcr:root');
		$xmlWriter->writeAttribute('xmlns:jcr', 'http://www.jcp.org/jcr/1.0');
		$xmlWriter->writeAttribute('xmlns:sv', 'http://www.jcp.org/jcr/sv/1.0');

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeUuid'));
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);

		$session->_call('exportPropertiesForSystemView', $node, $xmlWriter, TRUE);
		$xmlWriter->endElement();
		$xmlWriter->endDocument();

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());
		$this->assertEquals('jcr:uuid', (string)$xml->children('http://www.jcp.org/jcr/sv/1.0')->property[2]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
		$this->assertEquals('fakeUuid', (string)$xml->children('http://www.jcp.org/jcr/sv/1.0')->property[2]->value[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewExportsBinaryPropertyAsBase64() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('jcr:root');
		$xmlWriter->writeAttribute('xmlns:jcr', 'http://www.jcp.org/jcr/1.0');
		$xmlWriter->writeAttribute('xmlns:sv', 'http://www.jcp.org/jcr/sv/1.0');

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');

		$value = $this->getMock('F3\PHPCR\ValueInterface');
		$value->expects($this->once())->method('getString')->will($this->returnValue('not base 64 encoded at all'));

		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->any())->method('getName')->will($this->returnValue('binaryProperty'));
		$property->expects($this->any())->method('getType')->will($this->returnValue(\F3\PHPCR\PropertyType::BINARY));
		$property->expects($this->any())->method('isMultiple')->will($this->returnValue(FALSE));
		$property->expects($this->any())->method('getValue')->will($this->returnValue($value));

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array($property)));
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);

		$session->_call('exportPropertiesForSystemView', $node, $xmlWriter, TRUE);
		$xmlWriter->endElement();
		$xmlWriter->endDocument();

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());

		$children = $xml->children('http://www.jcp.org/jcr/sv/1.0');
		$this->assertEquals('binaryProperty', (string)$children->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->name);
		$this->assertEquals('Binary', (string)$children->property[3]->attributes('http://www.jcp.org/jcr/sv/1.0')->type);
		$this->assertEquals(base64_encode('not base 64 encoded at all'), (string)$children->property[3]->value[0]);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportSystemViewSkipsBinaryPropertyIfRequested() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');

		$value = $this->getMock('F3\PHPCR\ValueInterface');
		$value->expects($this->never())->method('getString');

		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->any())->method('getName')->will($this->returnValue('binaryProp'));
		$property->expects($this->any())->method('getType')->will($this->returnValue(\F3\PHPCR\PropertyType::BINARY));
		$property->expects($this->any())->method('isMultiple')->will($this->returnValue(TRUE));
		$property->expects($this->any())->method('getValues')->will($this->returnValue(array($value)));

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array($property)));
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);

		$session->_call('exportPropertiesForSystemView', $node, $xmlWriter, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsBinaryPropertyAsBase64() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('jcr:root');
		$xmlWriter->writeAttribute('xmlns:jcr', 'http://www.jcp.org/jcr/1.0');
		$xmlWriter->writeAttribute('xmlns:sv', 'http://www.jcp.org/jcr/sv/1.0');

		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');

		$value = $this->getMock('F3\PHPCR\ValueInterface');
		$value->expects($this->once())->method('getString')->will($this->returnValue('not base 64 encoded at all'));

		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->any())->method('getName')->will($this->returnValue('binaryProperty'));
		$property->expects($this->any())->method('getType')->will($this->returnValue(\F3\PHPCR\PropertyType::BINARY));
		$property->expects($this->any())->method('isMultiple')->will($this->returnValue(FALSE));
		$property->expects($this->any())->method('getValue')->will($this->returnValue($value));

		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array($property)));
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($nodeType));
		$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);

		$session->_call('exportPropertiesForDocumentView', $node, $xmlWriter, TRUE);
		$xmlWriter->endElement();
		$xmlWriter->endDocument();

		$xml = new \SimpleXMLElement($xmlWriter->outputMemory());

		$this->assertEquals(base64_encode('not base 64 encoded at all'), (string)$xml->attributes()->binaryProperty);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function exportDocumentViewExportsXMLTextNodesAsXMLText() {
		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();

		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->once())->method('getString')->will($this->returnValue('This is some XML text containing <weird> "stuff"'));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getDepth')->will($this->returnValue(1));
		$node->expects($this->any())->method('getName')->will($this->returnValue('jcr:xmltext'));
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array('jcr:xmlcharacters')));
		$node->expects($this->once())->method('hasProperty')->with('jcr:xmlcharacters')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('getProperty')->with('jcr:xmlcharacters')->will($this->returnValue($property));
		$node->expects($this->any())->method('hasNodes')->will($this->returnValue(FALSE));
       	$session = $this->getAccessibleMock('F3\TYPO3CR\Session', array('getNamespacePrefixes'), array(), '', FALSE);

		$session->_call('exportToXML', $node, $xmlWriter, FALSE, FALSE, \F3\TYPO3CR\Session::EXPORT_DOCUMENT, TRUE);
	}

}

?>