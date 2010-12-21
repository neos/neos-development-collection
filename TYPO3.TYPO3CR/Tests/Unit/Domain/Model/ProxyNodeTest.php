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
 * Testcase for the "ProxyNode" domain model
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProxyNodeTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var F3\TYPO3CR\Domain\Model\ProxyNode
	 */
	protected $proxyNode;

	/**
	 * @var F3\TYPO3CR\Domain\Model\Node
	 */
	protected $newNode;

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aProxyNodeIsRelatedToAnOriginalNode() {
		$originalNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		new \F3\TYPO3CR\Domain\Model\ProxyNode($originalNode);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theOriginalNodeMustNotBeAProxyNode() {
		$originalNode = $this->getMock('F3\TYPO3CR\Domain\Model\ProxyNode', array(), array(), '', FALSE);
		new \F3\TYPO3CR\Domain\Model\ProxyNode($originalNode);
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneOriginalNodeCallback() {
		$this->proxyNode->_set('newNode', $this->newNode);
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode($methodName, $argument1 = NULL, $argument2 = NULL) {
		$this->newNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);

		if ($argument1 === NULL) {
			$this->newNode->expects($this->at(0))->method($methodName);
			$this->newNode->expects($this->at(1))->method($methodName);
		} elseif ($argument2 === NULL) {
			$this->newNode->expects($this->at(0))->method($methodName)->with($argument1);
			$this->newNode->expects($this->at(1))->method($methodName)->with($argument1);
		} else {
			$this->newNode->expects($this->at(0))->method($methodName)->with($argument1, $argument2);
			$this->newNode->expects($this->at(1))->method($methodName)->with($argument1, $argument2);

		}

		$this->proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('cloneOriginalNode'), array(), '', FALSE);
		$this->proxyNode->expects($this->once())->method('cloneOriginalNode')->will($this->returnCallback(array($this, 'cloneOriginalNodeCallback')));

		call_user_func_array(array($this->proxyNode, $methodName), array($argument1, $argument2));
		call_user_func_array(array($this->proxyNode, $methodName), array($argument1, $argument2));
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function assertThatOriginalOrNewNodeIsCalled($methodName, $argument1 = NULL) {
		$originalNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		if ($argument1 === NULL) {
			$originalNode->expects($this->once())->method($methodName)->will($this->returnValue('originalNodeResult'));
		} else {
			$originalNode->expects($this->once())->method($methodName)->with($argument1)->will($this->returnValue('originalNodeResult'));
		}

		$newNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		if ($argument1 === NULL) {
			$newNode->expects($this->once())->method($methodName)->will($this->returnValue('newNodeResult'));
		} else {
			$newNode->expects($this->once())->method($methodName)->with($argument1)->will($this->returnValue('newNodeResult'));
		}

		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('dummy'), array(), '', FALSE);
		$proxyNode->_set('originalNode', $originalNode);

		$this->assertEquals('originalNodeResult', $proxyNode->$methodName($argument1));
		$proxyNode->_set('newNode', $newNode);
		$this->assertEquals('newNodeResult', $proxyNode->$methodName($argument1));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPathSetsThePathOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('setPath', '/foo/bar');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathRetrievesThePathFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getPath');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDepthRetrievesTheDepthFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getDepth');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNameRetrievesTheNameFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getWorkspaceRetrievesTheWorkspaceFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getWorkspace');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIdentifierReturnsTheIdentifier() {
		$originalNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$originalNode->expects($this->once())->method('getIdentifier')->will($this->returnValue('theidentifier'));

		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('dummy'), array(), '', FALSE);
		$proxyNode->_set('originalNode', $originalNode);

		$this->assertEquals('theidentifier', $proxyNode->getIdentifier());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setIndexSetsTheIndexOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('setIndex', 5);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIndexRetrievesTheIndexFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getIndex');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentRetrievesTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getParent');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function moveBeforeCallsMoveBeforeOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$referenceNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('moveBefore', $referenceNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPropertySetsThePropertyOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('setProperty', 'propertyName', 'value');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasPropertyCallsHasPropertyOnTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('hasProperty', 'myProperty');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyCallsGetPropertyOnTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getProperty', 'myProperty');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertiesCallsGetPropertiesOnTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getProperties');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyNamesCallsGetPropertyNamesOnTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getPropertyNames');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentObjectSetsTheContentObjectOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$contentObject = new \stdClass();
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('setContentObject', $contentObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentObjectCallsGetContentObjectOnTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getContentObject');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetContentObjectUnsetsTheContentObjectOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('unsetContentObject');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentTypeSetsTheContentTypeOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('setContentType');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentTypeCallsGetContentTypeOnTheParentNodeFromTheOriginalOrNewNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getContentType');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createNodeCallsCreateNodeOnTheNewNodeOrTheOriginalNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('createNode', 'nodename', 'MyContentType');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeCallsGetNodeOnTheNewNodeOrTheOriginalNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getNode', '/foo/bar');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPrimaryChildNodeCallsGetPrimaryChildNodeOnTheNewNodeOrTheOriginalNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getPrimaryChildNode');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesCallsGetChildNodesOnTheNewNodeOrTheOriginalNode() {
		$this->assertThatOriginalOrNewNodeIsCalled('getChildNodes', 'MyContentType');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeCallsRemoveOnTheNewNodeAndClonesTheOriginalNodeIfNoNewNodeExistedYet() {
		$this->assertThatOriginalNodeIsClonedAndMethodIsCalledOnNewNode('remove');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabelUsesGetterMethodsToRenderTheLabel() {
		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('getContentType', 'getName', 'hasProperty'), array(), '', FALSE);
		$proxyNode->expects($this->once())->method('hasProperty')->with('title')->will($this->returnValue(FALSE));
		$proxyNode->expects($this->once())->method('getName')->will($this->returnValue('thename'));
		$proxyNode->expects($this->once())->method('getContentType')->will($this->returnValue('TYPO3:TheContentType'));

		$this->assertSame('(TYPO3:TheContentType) thename', $proxyNode->getLabel());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContextSetsTheContextOfTheProxyNodeTheOriginalNodeAndTheNewNode() {
		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);

		$originalNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array('setContext'), array(), '', FALSE);
		$originalNode->expects($this->exactly(2))->method('setContext')->with($context);

		$newNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array('setContext'), array(), '', FALSE);
		$newNode->expects($this->once())->method('setContext')->with($context);

		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('dummy'), array(), '', FALSE);
		$proxyNode->_set('originalNode', $originalNode);

		$proxyNode->setContext($context);
		$proxyNode->_set('newNode', $newNode);
		$proxyNode->setContext($context);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContextReturnsTheContext() {
		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$originalNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array('setContext'), array(), '', FALSE);

		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('dummy'), array(), '', FALSE);
		$proxyNode->_set('originalNode', $originalNode);

		$proxyNode->setContext($context);
		$this->assertSame($context, $proxyNode->getContext());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneOriginalNodeCreatesACloneOfTheOriginalNode() {
		$workspace = $this->getMock('F3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$context = $this->getMock('F3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($workspace));

		$contentTypeManager = $this->getMock('F3\TYPO3CR\Domain\Service\ContentTypeManager', array(), array(), '', FALSE);
		$contentTypeManager->expects($this->any())->method('hasContentType')->will($this->returnValue(TRUE));

		$originalNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/foo', $workspace));
		$originalNode->_set('contentTypeManager', $contentTypeManager);

		$newNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\Node', array('dummy'), array('/foo', $workspace));
		$newNode->_set('contentTypeManager', $contentTypeManager);

		$nodeRepository = $this->getMock('F3\TYPO3CR\Domain\Repository\NodeRepository', array(), array(), '', FALSE);
		$nodeRepository->expects($this->once())->method('add')->with($newNode);

		$objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('create')->with('F3\TYPO3CR\Domain\Model\Node', '/foo', $workspace)->will($this->returnValue($newNode));

		$proxyNode = $this->getAccessibleMock('F3\TYPO3CR\Domain\Model\ProxyNode', array('dummy'), array(), '', FALSE);
		$proxyNode->_set('objectManager', $objectManager);
		$proxyNode->_set('nodeRepository', $nodeRepository);
		$proxyNode->_set('context', $context);
		$proxyNode->_set('originalNode', $originalNode);

		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
				public $body = "My Body";
			}
		');
		$contentObject = new $className;

		$originalNode->setContentObject($contentObject);
		$originalNode->setProperty('title', 'Foo');
		$originalNode->setProperty('body', 'Bar');

		$proxyNode->_call('cloneOriginalNode');

		$this->assertEquals($newNode->getProperties(), $originalNode->getProperties());
		$this->assertEquals($newNode->getIndex(), $originalNode->getIndex());
		$this->assertEquals($newNode->getContentType(), $originalNode->getContentType());
		$this->assertSame($newNode->getContentObject(), $originalNode->getContentObject());
		$this->assertSame($context, $newNode->getContext());
	}
}