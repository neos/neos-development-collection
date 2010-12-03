<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "Node" domain model
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorSetsPathWorkspaceAndIdentifier() {
		$mockWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$node = new \F3\TYPO3CR\Domain\Model\Node('/foo/bar', $mockWorkspace, '12345abcde');
		$this->assertSame('/foo/bar', $node->getPath());
		$this->assertSame('bar', $node->getName());
		$this->assertSame($mockWorkspace, $node->getWorkspace());
		$this->assertSame('12345abcde', $node->getIdentifier());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPathThrowsAnExceptionIfAnInvalidPathIsPassed() {
		$mockWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $mockWorkspace);
		$node->setPath('foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDepthReturnsThePathDepthOfTheNode() {
		$mockWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $mockWorkspace);
		$this->assertEquals(0, $node->getDepth());

		$node->setPath('/foo');
		$this->assertEquals(1, $node->getDepth());

		$node->setPath('/foo/bar');
		$this->assertEquals(2, $node->getDepth());

		$node->setPath('/foo/bar/baz/quux');
		$this->assertEquals(4, $node->getDepth());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWorkspacesAllowsForSettingTheWorkspaceForInternalPurposes() {
		$originalWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$newWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $originalWorkspace);
		$this->assertSame($originalWorkspace, $node->getWorkspace());

		$node->setWorkspace($newWorkspace);
		$this->assertSame($newWorkspace, $node->getWorkspace());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theIndexCanBeSetAndRetrieved() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);

		$node->setIndex(2);
		$this->assertEquals(2, $node->getIndex());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentReturnsNullForARootNode() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);
		$this->assertNull($node->getParent());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentReturnsParentNodeInCurrentNodesContext() {
		$contextWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($contextWorkspace));

		$currentNodeWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$expectedParentNode = new \F3\TYPO3CR\Domain\Model\Node('/foo', $currentNodeWorkspace);

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findOneByPath')->with('/foo', $contextWorkspace)->will($this->returnValue($expectedParentNode));

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array('/foo/bar', $currentNodeWorkspace));
		$currentNode->_set('nodeRepository', $nodeRepository);
		$currentNode->setContext($context);

		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($expectedParentNode)->will($this->returnValue($expectedParentNode));

		$actualParentNode = $currentNode->getParent();
		$this->assertSame($expectedParentNode, $actualParentNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aContentObjectCanBeSetRetrievedAndUnset() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);

		$contentObject = new \stdClass();

		$node->setContentObject($contentObject);
		$this->assertSame($contentObject, $node->getContentObject());

		$node->unsetContentObject();
		$this->assertNull($node->getContentObject());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aContentObjectMustBeAnObject() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);
		$node->setContentObject('not an object');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertiesCanBeSetAndRetrieved() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);

		$node->setProperty('title', 'My Title');
		$node->setProperty('body', 'My Body');

		$this->assertTrue($node->hasProperty('title'));
		$this->assertFalse($node->hasProperty('iltfh'));

		$this->assertEquals('My Body', $node->getProperty('body'));
		$this->assertEquals('My Title', $node->getProperty('title'));

		$this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $node->getProperties());

		$actualPropertyNames = $node->getPropertyNames();
		sort($actualPropertyNames);
		$this->assertEquals(array('body', 'title'), $actualPropertyNames);

	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertyFunctionsUseAContentObjectIfOneHasBeenDefined() {
		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
				public $body = "My Body";
			}
		');
		$contentObject = new $className;

		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);
		$node->setContentObject($contentObject);

		$this->assertTrue($node->hasProperty('title'));
		$this->assertFalse($node->hasProperty('iltfh'));

		$this->assertEquals('My Body', $node->getProperty('body'));
		$this->assertEquals('My Title', $node->getProperty('title'));

		$this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $node->getProperties());

		$actualPropertyNames = $node->getPropertyNames();
		sort($actualPropertyNames);
		$this->assertEquals(array('body', 'title'), $actualPropertyNames);

		$node->setProperty('title', 'My Other Title');
		$node->setProperty('body', 'My Other Body');

		$this->assertEquals('My Other Body', $node->getProperty('body'));
		$this->assertEquals('My Other Title', $node->getProperty('title'));
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3CR\Exception\NodeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyThrowsAnExceptionIfTheSpecifiedPropertyDoesNotExistInTheContentObject() {
		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
			}
		');
		$contentObject = new $className;

		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = new \F3\TYPO3CR\Domain\Model\Node('/', $workspace);
		$node->setContentObject($contentObject);

		$node->getProperty('foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theContentTypeCanBeSetAndRetrieved() {
		$contentTypeManager = $this->getMock('F3\TYPO3CR\Domain\Service\ContentTypeManager', array(), array(), '', FALSE);
		$contentTypeManager->expects($this->once())->method('hasContentType')->with('typo3:mycontent')->will($this->returnValue(TRUE));

		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/', $workspace));
		$node->_set('contentTypeManager', $contentTypeManager);

		$this->assertEquals('unstructured', $node->getContentType());

		$node->setContentType('typo3:mycontent');
		$this->assertEquals('typo3:mycontent', $node->getContentType());
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3CR\Exception\NodeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentTypeThrowsAnExceptionIfTheSpecifiedContentTypeDoesNotExist() {
		$contentTypeManager = $this->getMock('F3\TYPO3CR\Domain\Service\ContentTypeManager', array(), array(), '', FALSE);
		$contentTypeManager->expects($this->once())->method('hasContentType')->with('somecontenttype')->will($this->returnValue(FALSE));

		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$node = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/', $workspace));
		$node->_set('contentTypeManager', $contentTypeManager);

		$node->setContentType('somecontenttype');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createNodeCreatesAChildNodeOfTheCurrentNodeInTheContextWorkspace() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($workspace));

		$newNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array('/foo', $workspace));
		$newNode->expects($this->once())->method('setIndex')->with(1);
		$newNode->expects($this->once())->method('setContentType')->with('mycontenttype');

		$objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('create')->with('F3\TYPO3CR\Domain\Model\Node', '/foo', $workspace)->will($this->returnValue($newNode));

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('countByParentAndContentType', 'add'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('countByParentAndContentType')->with('/', NULL, $workspace)->will($this->returnValue(0));
		$nodeRepository->expects($this->once())->method('add')->with($newNode);

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array('/', $workspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('objectManager', $objectManager);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($newNode)->will($this->returnValue($newNode));

		$this->assertSame($newNode, $currentNode->createNode('foo', 'mycontenttype'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeReturnsTheSpecifiedNodeInTheCurrentNodesContext() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($workspace));

		$expectedNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array('/foo/bar', $workspace));

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findOneByPath')->with('/foo/bar', $workspace)->will($this->returnValue($expectedNode));

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('normalizePath', 'treatNodeWithContext'), array('/foo/baz', $workspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('normalizePath')->with('../bar')->will($this->returnValue('/foo/bar'));
		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($expectedNode)->will($this->returnValue($expectedNode));

		$actualNode = $currentNode->getNode('../bar');
		$this->assertSame($expectedNode, $actualNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($workspace));

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('findOneByPath'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findOneByPath')->with('/foo/quux', $workspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('normalizePath'), array('/foo/baz', $workspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('normalizePath')->with('/foo/quux')->will($this->returnValue('/foo/quux'));

		$this->assertNull($currentNode->getNode('/foo/quux'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPrimaryChildNodeReturnsTheFirstChildNode() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->any())->method('getWorkspace')->will($this->returnValue($workspace));

		$expectedNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array('/foo/bar', $workspace));

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('findFirstByParentAndContentType'), array(), '', FALSE);
		$nodeRepository->expects($this->at(0))->method('findFirstByParentAndContentType')->with('/foo', NULL, $workspace)->will($this->returnValue($expectedNode));
		$nodeRepository->expects($this->at(1))->method('findFirstByParentAndContentType')->with('/foo', NULL, $workspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array('/foo', $workspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('treatNodeWithContext')->with($expectedNode)->will($this->returnValue($expectedNode));

		$actualNode = $currentNode->getPrimaryChildNode();
		$this->assertSame($expectedNode, $actualNode);

		$this->assertNull($currentNode->getPrimaryChildNode());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesReturnsChildNodesInCurrentContextOptionallyFilteredyByContentType() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($workspace));

		$childNodes = array(
			$this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array('/foo/bar', $workspace))
		);

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('findByParentAndContentType'), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('findByParentAndContentType')->with('/foo', 'mycontenttype', $workspace)->will($this->returnValue($childNodes));

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('treatNodesWithContext'), array('/foo', $workspace));
		$currentNode->_set('context', $context);
		$currentNode->_set('nodeRepository', $nodeRepository);

		$currentNode->expects($this->once())->method('treatNodesWithContext')->with($childNodes)->will($this->returnValue($childNodes));

		$this->assertSame($childNodes, $currentNode->getChildNodes('mycontenttype'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeRemovesAllChildNodesAndTheNodeItself() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue(NULL));

		$subNode1 = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('remove'), array('/foo/bar1', $workspace));
		$subNode1->expects($this->once())->method('remove');

		$subNode2 = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('remove'), array('/foo/bar2', $workspace));
		$subNode2->expects($this->once())->method('remove');

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('remove'), array(), '', FALSE);

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('getChildNodes'), array('/foo', $workspace));
		$currentNode->_set('nodeRepository', $nodeRepository);
		$currentNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($subNode1, $subNode2)));

		$nodeRepository->expects($this->once())->method('remove')->with($currentNode);

		$currentNode->remove();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeOnlyFlagsTheNodeAsRemovedIfItsWorkspaceHasAnotherBaseWorkspace() {
		$baseWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue($baseWorkspace));

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array('remove'), array(), '', FALSE);

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('getChildNodes'), array('/foo', $workspace));
		$currentNode->_set('nodeRepository', $nodeRepository);
		$currentNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array()));

		$nodeRepository->expects($this->never())->method('remove');

		$currentNode->remove();

		$this->assertTrue($currentNode->isRemoved());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theContextCanBeSetAndRetrieved() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/foo', $workspace));

		$currentNode->setContext($context);
		$this->assertSame($context, $currentNode->getContext());
	}

	/**
	 * @test
	 * @dataProvider abnormalPaths
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath) {
		$node = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$node->_set('path', $currentPath);
		$this->assertSame($normalizedPath, $node->_call('normalizePath', $relativePath));
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function normalizePathThrowsInvalidArgumentExceptionOnPathContainingDoubleSlash() {
		$node = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$node->_call('normalizePath', 'foo//bar');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function treatNodesWithContextWillTreatEachGivenNodeWithContext() {
		$nodes = array(
			1 => $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE),
			2 => $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE),
		);

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('treatNodeWithContext'), array(), '', FALSE);
		$currentNode->expects($this->exactly(2))->method('treatNodeWithContext')->will($this->returnArgument(0));

		$returnedNodes = $currentNode->_call('treatNodesWithContext', $nodes);
		$this->assertEquals($nodes, $returnedNodes);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function treatNodeWithContextWillTreatTheGivenNodeWithContextOfThisNode() {
		$currentWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array('getWorkspace'), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($currentWorkspace));

		$subjectNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('getWorkspace', 'setContext'), array(), '', FALSE);
		$subjectNode->expects($this->once())->method('getWorkspace')->will($this->returnValue($currentWorkspace));
		$subjectNode->expects($this->once())->method('setContext')->with($context);

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$currentNode->_set('context', $context);

		$returnedNode = $currentNode->_call('treatNodeWithContext', $subjectNode);
		$this->assertEquals($subjectNode, $returnedNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function treatNodeWithContextReturnsAProxyNodeIfTheWorkspaceOfTheGivenNodeIsDifferentThanTheWorkspaceOfThisNode() {
		$otherWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$currentWorkspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array('getWorkspace'), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($currentWorkspace));

		$subjectNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('getWorkspace'), array(), '', FALSE);
		$subjectNode->expects($this->once())->method('getWorkspace')->will($this->returnValue($otherWorkspace));

		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('setContext'), array(), '', FALSE);
		$proxyNode->expects($this->once())->method('setContext')->with($context);

		$objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('create')->with('F3\TYPO3CR\Domain\Model\ProxyNode', $subjectNode)->will($this->returnValue($proxyNode));

		$currentNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array(), '', FALSE);
		$currentNode->_set('objectManager', $objectManager);
		$currentNode->_set('context', $context);

		$returnedNode = $currentNode->_call('treatNodeWithContext', $subjectNode);
		$this->assertEquals($proxyNode, $returnedNode);
	}

}