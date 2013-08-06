<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "NodeData" domain model
 */
class NodeDataTest extends \TYPO3\Flow\Tests\UnitTestCase {

	protected $mockWorkspace;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $node;

	public function setUp() {
		$this->mockWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$this->node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array('/foo/bar', $this->mockWorkspace));
		$this->node->_set('nodeDataRepository', $this->getMock('TYPO3\Flow\Persistence\RepositoryInterface'));
	}

	/**
	 * @test
	 */
	public function constructorSetsPathWorkspaceAndIdentifier() {
		$node = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/foo/bar', $this->mockWorkspace, '12345abcde');
		$this->assertSame('/foo/bar', $node->getPath());
		$this->assertSame('bar', $node->getName());
		$this->assertSame($this->mockWorkspace, $node->getWorkspace());
		$this->assertSame('12345abcde', $node->getIdentifier());
	}

	/**
	 * @test
	 */
	public function getAbstractReturnsAnAbstract() {
		$this->node->setProperty('title', 'The title of this node');
		$this->node->setProperty('text', 'Shall I or <em>shall</em> I not, leak or not leak?');

		$this->assertEquals('The title of this node – Shall I or shall I not, leak or not leak?', $this->node->getAbstract());
	}

	/**
	 * @test
	 */
	public function getAbstractIgnoresPropertiesWhichAreObjects() {
		$this->node->setProperty('title', 'The title of this node');
		$this->node->setProperty('subtitle', 'The sub title');
		$this->node->setProperty('uri', new \TYPO3\Flow\Http\Uri('http://localhost'));
		$this->node->setProperty('myObject', new \stdClass());

		$this->assertEquals('The title of this node – The sub title – http://localhost', $this->node->getAbstract());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @dataProvider invalidPaths()
	 */
	public function setPathThrowsAnExceptionIfAnInvalidPathIsPassed($path) {
		$this->node->_call('setPath', $path, FALSE);
	}

	/**
	 */
	public function invalidPaths() {
		return array(
			array('foo'),
			array('/ '),
			array('//'),
			array('/foo//bar'),
			array('/foo/ bar'),
			array('/foo/bar/'),
			array('/123 bar'),
		);
	}

	/**
	 * @test
	 * @dataProvider validPaths()
	 */
	public function setPathAcceptsAValidPath($path) {
		$this->node->_call('setPath', $path, FALSE);
			// dummy assertion to avoid PHPUnit warning in strict mode
		$this->assertTrue(TRUE);
	}

	/**
	 */
	public function validPaths() {
		return array(
			array('/foo'),
			array('/foo/bar'),
			array('/foo/bar/baz'),
			array('/12/foo'),
			array('/12356'),
			array('/foo-bar'),
			array('/foo-bar/1-5'),
			array('/foo-bar/bar/asdkak/dsflasdlfkjasd/asdflnasldfkjalsd/134-111324823-234234-234/sdasdflkj'),
		);
	}

	/**
	 * @test
	 */
	public function getDepthReturnsThePathDepthOfTheNode() {
		$node = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/', $this->mockWorkspace);
		$this->assertEquals(0, $node->getDepth());

		$node = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/foo', $this->mockWorkspace);
		$this->assertEquals(1, $node->getDepth());

		$node = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/foo/bar', $this->mockWorkspace);
		$this->assertEquals(2, $node->getDepth());

		$node = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/foo/bar/baz/quux', $this->mockWorkspace);
		$this->assertEquals(4, $node->getDepth());
	}

	/**
	 * @test
	 */
	public function setWorkspacesAllowsForSettingTheWorkspaceForInternalPurposes() {
		$newWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$this->assertSame($this->mockWorkspace, $this->node->getWorkspace());

		$this->node->setWorkspace($newWorkspace);
		$this->assertSame($newWorkspace, $this->node->getWorkspace());
	}

	/**
	 * @test
	 */
	public function theIndexCanBeSetAndRetrieved() {
		$this->node->setIndex(2);
		$this->assertEquals(2, $this->node->getIndex());
	}

	/**
	 * @test
	 */
	public function getParentReturnsNullForARootNode() {
		$node = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/', $this->mockWorkspace);
		$this->assertNull($node->getParent());
	}

	/**
	 * @test
	 */
	public function aContentObjectCanBeSetRetrievedAndUnset() {
		$contentObject = new \stdClass();

		$this->node->setContentObject($contentObject);
		$this->assertSame($contentObject, $this->node->getContentObject());

		$this->node->unsetContentObject();
		$this->assertNull($this->node->getContentObject());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function aContentObjectMustBeAnObject() {
		$this->node->setContentObject('not an object');
	}

	/**
	 * @test
	 */
	public function propertiesCanBeSetAndRetrieved() {
		$this->node->setProperty('title', 'My Title');
		$this->node->setProperty('body', 'My Body');

		$this->assertTrue($this->node->hasProperty('title'));
		$this->assertFalse($this->node->hasProperty('iltfh'));

		$this->assertEquals('My Body', $this->node->getProperty('body'));
		$this->assertEquals('My Title', $this->node->getProperty('title'));

		$this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $this->node->getProperties());

		$actualPropertyNames = $this->node->getPropertyNames();
		sort($actualPropertyNames);
		$this->assertEquals(array('body', 'title'), $actualPropertyNames);

	}

	/**
	 * @test
	 */
	public function propertiesCanBeRemoved() {
		$this->node->setProperty('title', 'My Title');
		$this->assertTrue($this->node->hasProperty('title'));

		$this->node->removeProperty('title');

		$this->assertFalse($this->node->hasProperty('title'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function removePropertyThrowsExceptionIfPropertyDoesNotExist() {
		$this->node->removeProperty('nada');
	}

	/**
	 * @test
	 */
	public function removePropertyDoesNotTouchAContentObject() {
		$this->node->_set('persistenceManager', $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface'));

		$className = uniqid('Test');
		eval('class ' .$className . ' {
				public $title = "My Title";
			}');
		$contentObject = new $className();
		$this->node->setContentObject($contentObject);

		$this->node->removeProperty('title');

		$this->assertTrue($this->node->hasProperty('title'));
		$this->assertEquals('My Title', $this->node->getProperty('title'));
	}

	/**
	 * @test
	 */
	public function propertyFunctionsUseAContentObjectIfOneHasBeenDefined() {
		$this->node->_set('persistenceManager', $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface'));

		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
				public $body = "My Body";
			}
		');
		$contentObject = new $className;

		$this->node->setContentObject($contentObject);

		$this->assertTrue($this->node->hasProperty('title'));
		$this->assertFalse($this->node->hasProperty('iltfh'));

		$this->assertEquals('My Body', $this->node->getProperty('body'));
		$this->assertEquals('My Title', $this->node->getProperty('title'));

		$this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $this->node->getProperties());

		$actualPropertyNames = $this->node->getPropertyNames();
		sort($actualPropertyNames);
		$this->assertEquals(array('body', 'title'), $actualPropertyNames);

		$this->node->setProperty('title', 'My Other Title');
		$this->node->setProperty('body', 'My Other Body');

		$this->assertEquals('My Other Body', $this->node->getProperty('body'));
		$this->assertEquals('My Other Title', $this->node->getProperty('title'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function getPropertyThrowsAnExceptionIfTheSpecifiedPropertyDoesNotExistInTheContentObject() {
		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
			}
		');
		$contentObject = new $className;
		$this->node->setContentObject($contentObject);

		$this->node->getProperty('foo');
	}

	/**
	 * @test
	 */
	public function theNodeTypeCanBeSetAndRetrieved() {
		$nodeTypeManager = $this->getMock('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$nodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnCallback(function ($name) { return new \TYPO3\TYPO3CR\Domain\Model\NodeType($name, array(), array()) ;}));

		$this->node->_set('nodeTypeManager', $nodeTypeManager);

		$this->assertEquals('unstructured', $this->node->getNodeType()->getName());

		$myNodeType = $nodeTypeManager->getNodeType('typo3:mycontent');
		$this->node->setNodeType($myNodeType);
		$this->assertEquals($myNodeType, $this->node->getNodeType());
	}

	/**
	 * @test
	 */
	public function createNodeCreatesAChildNodeOfTheCurrentNodeInTheContextWorkspace() {
		$this->marktestIncomplete('Should be refactored to a contextualized node test.');

		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('countByParentAndNodeType', 'add'), array(), '', FALSE);
		$nodeDataRepository->expects($this->once())->method('countByParentAndNodeType')->with('/', NULL, $this->mockWorkspace)->will($this->returnValue(0));
		$nodeDataRepository->expects($this->once())->method('add');
		$nodeDataRepository->expects($this->any())->method('getContext')->will($this->returnValue($context));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getNode'), array('/', $this->mockWorkspace));
		$currentNode->_set('nodeDataRepository', $nodeDataRepository);

		$currentNode->expects($this->once())->method('createProxyForContextIfNeeded')->will($this->returnArgument(0));
		$currentNode->expects($this->once())->method('filterNodeByContext')->will($this->returnArgument(0));

		$newNode = $currentNode->createNode('foo', 'mynodetype');
		$this->assertSame($currentNode, $newNode->getParent());
		$this->assertEquals(1, $newNode->getIndex());
		$this->assertEquals('mynodetype', $newNode->getNodeType()->getName());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function createNodeThrowsNodeExceptionIfPathAlreadyExists() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$oldNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo', $this->mockWorkspace));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getNode'), array('/', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->expects($this->once())->method('getNode')->with('/foo')->will($this->returnValue($oldNode));

		$currentNode->createNode('foo');
	}

	/**
	 * @test
	 */
	public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist() {
		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findOneByPath', 'getContext'), array(), '', FALSE);
		$nodeDataRepository->expects($this->once())->method('findOneByPath')->with('/foo/quux', $this->mockWorkspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('normalizePath', 'getContext'), array('/foo/baz', $this->mockWorkspace));
		$currentNode->_set('nodeDataRepository', $nodeDataRepository);
		$currentNode->expects($this->once())->method('normalizePath')->with('/foo/quux')->will($this->returnValue('/foo/quux'));

		$this->assertNull($currentNode->getNode('/foo/quux'));
	}

	/**
	 * @test
	 */
	public function getPrimaryChildNodeReturnsTheFirstChildNode() {
		$expectedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo/bar', $this->mockWorkspace));

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findFirstByParentAndNodeType'), array(), '', FALSE);
		$nodeDataRepository->expects($this->at(0))->method('findFirstByParentAndNodeType')->with('/foo', NULL, $this->mockWorkspace)->will($this->returnValue($expectedNode));
		$nodeDataRepository->expects($this->at(1))->method('findFirstByParentAndNodeType')->with('/foo', NULL, $this->mockWorkspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array('/foo', $this->mockWorkspace));
		$currentNode->_set('nodeDataRepository', $nodeDataRepository);

		$this->assertSame($currentNode->getWorkspace(), $this->mockWorkspace);

		$actualNode = $currentNode->getPrimaryChildNode();
		$this->assertSame($expectedNode, $actualNode);

		$this->assertNull($currentNode->getPrimaryChildNode());
	}

	/**
	 * @test
	 */
	public function getChildNodesReturnsChildNodesFilteredyByNodeType() {
		$childNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo/bar', $this->mockWorkspace));
		$nodeType = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array(), array('mynodetype', array(), array()));
		$childNode->setNodeType($nodeType);
		$childNodes = array(
			$childNode
		);

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findByParentAndNodeType'), array(), '', FALSE);
		$nodeDataRepository->expects($this->at(0))->method('findByParentAndNodeType')->with('/foo', 'mynodetype', $this->mockWorkspace)->will($this->returnValue($childNodes));
		$nodeDataRepository->expects($this->at(1))->method('findByParentAndNodeType')->with('/foo', 'notexistingnodetype', $this->mockWorkspace)->will($this->returnValue(array()));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array('/foo', $this->mockWorkspace));
		$currentNode->_set('nodeDataRepository', $nodeDataRepository);

		$this->assertSame($childNodes, $currentNode->getChildNodes('mynodetype'));
		$this->assertSame(array(), $currentNode->getChildNodes('notexistingnodetype'));
	}

	/**
	 * @test
	 */
	public function removeRemovesAllChildNodesAndTheNodeItself() {
		$this->mockWorkspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue(NULL));

		$subNode1 = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('remove'), array('/foo/bar1', $this->mockWorkspace));
		$subNode1->expects($this->once())->method('remove');

		$subNode2 = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('remove'), array('/foo/bar2', $this->mockWorkspace));
		$subNode2->expects($this->once())->method('remove');

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('remove'), array(), '', FALSE);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getChildNodes'), array('/foo', $this->mockWorkspace));
		$currentNode->_set('nodeDataRepository', $nodeDataRepository);
		$currentNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($subNode1, $subNode2)));

		$nodeDataRepository->expects($this->once())->method('remove')->with($currentNode);

		$currentNode->remove();
	}

	/**
	 * @test
	 */
	public function removeOnlyFlagsTheNodeAsRemovedIfItsWorkspaceHasAnotherBaseWorkspace() {
		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

		$baseWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$workspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue($baseWorkspace));

		$nodeDataRepository = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('remove'), array(), '', FALSE);
		$nodeDataRepository->_set('entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
		$nodeDataRepository->_set('persistenceManager', $mockPersistenceManager);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getChildNodes'), array('/foo', $workspace));
		$currentNode->_set('nodeDataRepository', $nodeDataRepository);
		$currentNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array()));

		$nodeDataRepository->expects($this->never())->method('remove');

		$currentNode->remove();

		$this->assertTrue($currentNode->isRemoved());
	}

	/**
	 * @test
	 */
	public function setRemovedCallsRemoveMethodIfArgumentIsTrue() {
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('remove'), array(), '', FALSE);
		$node->expects($this->once())->method('remove');
		$node->setRemoved(TRUE);
	}

	/**
	 * @test
	 * @dataProvider abnormalPaths
	 */
	public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath) {
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array(), '', FALSE);
		$node->_set('path', $currentPath);
		$this->assertSame($normalizedPath, $node->_call('normalizePath', $relativePath));
	}

	/**
	 */
	public function abnormalPaths() {
		return array(
			array('/', '/', '/'),
			array('/', '/.', '/'),
			array('/', '.', '/'),
			array('/', 'foo/bar', '/foo/bar'),
			array('/foo', '.', '/foo'),
			array('/foo', '/foo/.', '/foo'),
			array('/foo', '../', '/'),
			array('/foo/bar', '../baz', '/foo/baz'),
			array('/foo/bar', '../baz/../bar', '/foo/bar'),
			array('/foo/bar', '.././..', '/'),
			array('/foo/bar', '../../.', '/'),
			array('/foo/bar/baz', '../..', '/foo'),
			array('/foo/bar/baz', '../quux', '/foo/bar/quux'),
			array('/foo/bar/baz', '../quux/.', '/foo/bar/quux')
		);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function normalizePathThrowsInvalidArgumentExceptionOnPathContainingDoubleSlash() {
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array(), '', FALSE);
		$node->_call('normalizePath', 'foo//bar');
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsTrueIfAccessRolesIsNotSet() {
		$this->assertTrue($this->node->isAccessible());
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsFalseIfAccessRolesIsSetAndSecurityContextHasNoRoles() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->any())->method('hasRole')->will($this->returnValue(FALSE));
		$this->node->_set('securityContext', $mockSecurityContext);

		$this->node->setAccessRoles(array('SomeRole'));
		$this->assertFalse($this->node->isAccessible());
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsTrueIfAccessRolesIsSetAndSecurityContextHasOneOfTheRequiredRoles() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->at(0))->method('hasRole')->with('SomeRole')->will($this->returnValue(FALSE));
		$mockSecurityContext->expects($this->at(1))->method('hasRole')->with('SomeOtherRole')->will($this->returnValue(TRUE));
		$this->node->_set('securityContext', $mockSecurityContext);

		$this->node->setAccessRoles(array('SomeRole', 'SomeOtherRole'));
		$this->assertTrue($this->node->isAccessible());
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsTrueIfRoleIsEveryone() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->at(0))->method('hasRole')->with('SomeRole')->will($this->returnValue(FALSE));
		$mockSecurityContext->expects($this->at(1))->method('hasRole')->with('Everyone')->will($this->returnValue(TRUE));
		$this->node->_set('securityContext', $mockSecurityContext);

		$this->node->setAccessRoles(array('SomeRole', 'Everyone', 'SomeOtherRole'));
		$this->assertTrue($this->node->isAccessible());
	}

	/**
	 * @test
	 */
	public function createNodeCreatesNodeDataWithExplicitWorkspaceIfGiven() {
		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
		$this->inject($this->node, 'nodeDataRepository', $nodeDataRepository);

		$nodeDataRepository->expects($this->atLeastOnce())->method('add')->with($this->attributeEqualTo('workspace', $this->mockWorkspace));

		$this->node->createNode('foo', NULL, NULL, $this->mockWorkspace);
	}

}
?>