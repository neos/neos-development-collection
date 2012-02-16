<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "Node" domain model
 *
 */
class NodeTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	protected $mockWorkspace;
	protected $node;

	public function setUp() {
		$this->mockWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$this->node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/foo/bar', $this->mockWorkspace));
		$this->node->_set('nodeRepository', $this->getMock('TYPO3\FLOW3\Persistence\RepositoryInterface'));
	}

	/**
	 * @test
	 */
	public function constructorSetsPathWorkspaceAndIdentifier() {
		$node = new \TYPO3\TYPO3CR\Domain\Model\Node('/foo/bar', $this->mockWorkspace, '12345abcde');
		$this->assertSame('/foo/bar', $node->getPath());
		$this->assertSame('bar', $node->getName());
		$this->assertSame($this->mockWorkspace, $node->getWorkspace());
		$this->assertSame('12345abcde', $node->getIdentifier());
	}

	/**
	 * @test
	 */
	public function getLabelCropsTheLabelIfNecessary() {
		$this->assertEquals('(unstructured) bar', $this->node->getLabel());

		$this->node->setProperty('title', 'The point of this title is, that it`s a bit long and needs to be cropped.');
		$this->assertEquals('The point of this title is, th …', $this->node->getLabel());

		$this->node->setProperty('title', 'A better title');
		$this->assertEquals('A better title', $this->node->getLabel());
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
	 * @expectedException InvalidArgumentException
	 * @dataProvider invalidPaths()
	 */
	public function setPathThrowsAnExceptionIfAnInvalidPathIsPassed($path) {
		$this->node->setPath($path);
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
		$this->node->setPath($path);
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
		$node = new \TYPO3\TYPO3CR\Domain\Model\Node('/', $this->mockWorkspace);
		$this->assertEquals(0, $node->getDepth());

		$node = new \TYPO3\TYPO3CR\Domain\Model\Node('/foo', $this->mockWorkspace);
		$this->assertEquals(1, $node->getDepth());

		$node = new \TYPO3\TYPO3CR\Domain\Model\Node('/foo/bar', $this->mockWorkspace);
		$this->assertEquals(2, $node->getDepth());

		$node = new \TYPO3\TYPO3CR\Domain\Model\Node('/foo/bar/baz/quux', $this->mockWorkspace);
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
		$node = new \TYPO3\TYPO3CR\Domain\Model\Node('/', $this->mockWorkspace);
		$this->assertNull($node->getParent());
	}

	/**
	 * @test
	 */
	public function getParentReturnsParentNodeInCurrentNodesContext() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$currentNodeWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$expectedParentNode = new \TYPO3\TYPO3CR\Domain\Model\Node('/foo', $currentNodeWorkspace);

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findOneByPath')->with('/foo', $this->mockWorkspace)->will($this->returnValue($expectedParentNode));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array('/foo/bar', $currentNodeWorkspace));
		$currentNode->_set('nodeRepository', $nodeRepository);
		$currentNode->setContext($context);

		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($expectedParentNode)->will($this->returnValue($expectedParentNode));

		$actualParentNode = $currentNode->getParent();
		$this->assertSame($expectedParentNode, $actualParentNode);
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
	public function propertyFunctionsUseAContentObjectIfOneHasBeenDefined() {
		$this->node->_set('persistenceManager', $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface'));

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
	 * @expectedException TYPO3\TYPO3CR\Exception\NodeException
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
	public function theContentTypeCanBeSetAndRetrieved() {
		$contentTypeManager = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContentTypeManager', array(), array(), '', FALSE);
		$contentTypeManager->expects($this->once())->method('hasContentType')->with('typo3:mycontent')->will($this->returnValue(TRUE));

		$this->node->_set('contentTypeManager', $contentTypeManager);

		$this->assertEquals('unstructured', $this->node->getContentType());

		$this->node->setContentType('typo3:mycontent');
		$this->assertEquals('typo3:mycontent', $this->node->getContentType());
	}

	/**
	 * @test
	 * @expectedException TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function setContentTypeThrowsAnExceptionIfTheSpecifiedContentTypeDoesNotExist() {
		$contentTypeManager = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContentTypeManager', array(), array(), '', FALSE);
		$contentTypeManager->expects($this->once())->method('hasContentType')->with('somecontenttype')->will($this->returnValue(FALSE));

		$this->node->_set('contentTypeManager', $contentTypeManager);

		$this->node->setContentType('somecontenttype');
	}

	/**
	 * @test
	 */
	public function createNodeCreatesAChildNodeOfTheCurrentNodeInTheContextWorkspace() {
		$this->marktestIncomplete('The $newNode lacks some setter injections...');

		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('countByParentAndContentType', 'add'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('countByParentAndContentType')->with('/', NULL, $this->mockWorkspace)->will($this->returnValue(0));
		$nodeRepository->expects($this->once())->method('add');

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext', 'getNode'), array('/', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('treatNodeWithContext')->will($this->returnArgument(0));

		$newNode = $currentNode->createNode('foo', 'mycontenttype');
		$this->assertSame($currentNode, $newNode->getParent());
		$this->assertEquals(1, $newNode->getIndex());
		$this->assertEquals('mycontenttype', $newNode->getContentType());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function createNodeThrowsNodeExceptionIfPathAlreadyExists() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$oldNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array('/foo', $this->mockWorkspace));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getNode'), array('/', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->expects($this->once())->method('getNode')->with('/foo')->will($this->returnValue($oldNode));

		$currentNode->createNode('foo');
	}

	/**
	 * @test
	 */
	public function getNodeReturnsTheSpecifiedNodeInTheCurrentNodesContext() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$expectedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array('/foo/bar', $this->mockWorkspace));

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findOneByPath')->with('/foo/bar', $this->mockWorkspace)->will($this->returnValue($expectedNode));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('normalizePath', 'treatNodeWithContext'), array('/foo/baz', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('normalizePath')->with('../bar')->will($this->returnValue('/foo/bar'));
		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($expectedNode)->will($this->returnValue($expectedNode));

		$actualNode = $currentNode->getNode('../bar');
		$this->assertSame($expectedNode, $actualNode);
	}

	/**
	 * @test
	 */
	public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findOneByPath')->with('/foo/quux', $this->mockWorkspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('normalizePath'), array('/foo/baz', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('normalizePath')->with('/foo/quux')->will($this->returnValue('/foo/quux'));

		$this->assertNull($currentNode->getNode('/foo/quux'));
	}

	/**
	 * @test
	 */
	public function getPrimaryChildNodeReturnsTheFirstChildNode() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$expectedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array('/foo/bar', $this->mockWorkspace));

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findFirstByParentAndContentType'), array(), '', FALSE);
		$nodeRepository->expects($this->at(0))->method('findFirstByParentAndContentType')->with('/foo', NULL, $this->mockWorkspace)->will($this->returnValue($expectedNode));
		$nodeRepository->expects($this->at(1))->method('findFirstByParentAndContentType')->with('/foo', NULL, $this->mockWorkspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array('/foo', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($expectedNode)->will($this->returnValue($expectedNode));

		$actualNode = $currentNode->getPrimaryChildNode();
		$this->assertSame($expectedNode, $actualNode);

		$this->assertNull($currentNode->getPrimaryChildNode());
	}

	/**
	 * @test
	 */
	public function getChildNodesReturnsChildNodesInCurrentContextOptionallyFilteredyByContentType() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$childNodes = array(
			$this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array('/foo/bar', $this->mockWorkspace))
		);

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('findByParentAndContentType'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findByParentAndContentType')->with('/foo', 'mycontenttype', $this->mockWorkspace)->will($this->returnValue($childNodes));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('treatNodesWithContext'), array('/foo', $this->mockWorkspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('treatNodesWithContext')->with($childNodes)->will($this->returnValue($childNodes));

		$this->assertSame($childNodes, $currentNode->getChildNodes('mycontenttype'));
	}

	/**
	 * @test
	 */
	public function removeRemovesAllChildNodesAndTheNodeItself() {
		$this->mockWorkspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue(NULL));

		$subNode1 = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('remove'), array('/foo/bar1', $this->mockWorkspace));
		$subNode1->expects($this->once())->method('remove');

		$subNode2 = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('remove'), array('/foo/bar2', $this->mockWorkspace));
		$subNode2->expects($this->once())->method('remove');

		$nodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('remove'), array(), '', FALSE);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getChildNodes'), array('/foo', $this->mockWorkspace));
		$currentNode->_set('nodeRepository', $nodeRepository);
		$currentNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($subNode1, $subNode2)));

		$nodeRepository->expects($this->once())->method('remove')->with($currentNode);

		$currentNode->remove();
	}

	/**
	 * @test
	 */
	public function removeOnlyFlagsTheNodeAsRemovedIfItsWorkspaceHasAnotherBaseWorkspace() {
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');

		$baseWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$workspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue($baseWorkspace));

		$nodeRepository = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository', array('remove'), array(), '', FALSE);
		$nodeRepository->_set('entityClassName', 'TYPO3\TYPO3CR\Domain\Model\Node');
		$nodeRepository->_set('persistenceManager', $mockPersistenceManager);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getChildNodes'), array('/foo', $workspace));
		$currentNode->_set('nodeRepository', $nodeRepository);
		$currentNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array()));

		$nodeRepository->expects($this->never())->method('remove');

		$currentNode->remove();

		$this->assertTrue($currentNode->isRemoved());
	}

	/**
	 * @test
	 */
	public function theContextCanBeSetAndRetrieved() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);

		$this->node->setContext($context);
		$this->assertSame($context, $this->node->getContext());
	}

	/**
	 * @test
	 * @dataProvider abnormalPaths
	 */
	public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath) {
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
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
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$node->_call('normalizePath', 'foo//bar');
	}

	/**
	 * @test
	 */
	public function treatNodesWithContextWillTreatEachGivenNodeWithContext() {
		$nodes = array(
			1 => $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE),
			2 => $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE),
		);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array(), '', FALSE);
		$currentNode->expects($this->exactly(2))->method('treatNodeWithContext')->will($this->returnArgument(0));

		$returnedNodes = $currentNode->_call('treatNodesWithContext', $nodes);
		$this->assertEquals($nodes, $returnedNodes);
	}

	/**
	 * @test
	 */
	public function treatNodeWithContextWillTreatTheGivenNodeWithContextOfThisNode() {
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array('getWorkspace'), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$subjectNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getWorkspace', 'setContext', 'isvisible'), array(), '', FALSE);
		$subjectNode->expects($this->once())->method('isVisible')->will($this->returnValue(TRUE));
		$subjectNode->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));
		$subjectNode->expects($this->once())->method('setContext')->with($context);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$currentNode->_set('context', $context);

		$returnedNode = $currentNode->_call('treatNodeWithContext', $subjectNode);
		$this->assertEquals($subjectNode, $returnedNode);
	}

	/**
	 * @test
	 */
	public function treatNodeWithContextReturnsAProxyNodeIfTheWorkspaceOfTheGivenNodeIsDifferentThanTheWorkspaceOfThisNode() {
		$otherWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array('getWorkspace'), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$subjectNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('getWorkspace'), array(), '', FALSE);
		$subjectNode->expects($this->once())->method('getWorkspace')->will($this->returnValue($otherWorkspace));

		$proxyNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('setContext', 'isVisible'), array(), '', FALSE);
		$proxyNode->expects($this->once())->method('isVisible')->will($this->returnValue(TRUE));
		$proxyNode->expects($this->once())->method('setContext')->with($context);

		$proxyNodeFactory = $this->getMock('TYPO3\TYPO3CR\Domain\Factory\ProxyNodeFactory');
		$proxyNodeFactory->expects($this->once())->method('createFromNode')->with($subjectNode)->will($this->returnValue($proxyNode));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$currentNode->_set('proxyNodeFactory', $proxyNodeFactory);
		$currentNode->_set('context', $context);

		$returnedNode = $currentNode->_call('treatNodeWithContext', $subjectNode);
		$this->assertEquals($proxyNode, $returnedNode);
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
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
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
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
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
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->at(0))->method('hasRole')->with('SomeRole')->will($this->returnValue(FALSE));
		$mockSecurityContext->expects($this->at(1))->method('hasRole')->with('Everyone')->will($this->returnValue(TRUE));
		$this->node->_set('securityContext', $mockSecurityContext);

		$this->node->setAccessRoles(array('SomeRole', 'Everyone', 'SomeOtherRole'));
		$this->assertTrue($this->node->isAccessible());
	}
}