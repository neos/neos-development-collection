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
 * Testcase for the "Node" domain model
 */
class NodeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createNodeFromTemplateUsesWorkspaceFromContextForNodeData() {
		$workspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$parentNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', FALSE);
		$newNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array(), '', FALSE);

		$mockFirstLevelNodeCache = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Cache\FirstLevelNodeCache');
		$newNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));
		$nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();

		$context->expects($this->any())->method('getWorkspace')->will($this->returnValue($workspace));

		$nodeFactory = $this->getMock('TYPO3\TYPO3CR\Domain\Factory\NodeFactory');

		$parentNode = new \TYPO3\TYPO3CR\Domain\Model\Node($parentNodeData, $context);

		$this->inject($parentNode, 'nodeFactory', $nodeFactory);

		$parentNodeData->expects($this->atLeastOnce())->method('createNodeFromTemplate')->with($nodeTemplate, 'bar', $workspace)->will($this->returnValue($newNodeData));
		$nodeFactory->expects($this->atLeastOnce())->method('createFromNodeData')->with($newNodeData, $context)->will($this->returnValue($newNode));

		$parentNode->createNodeFromTemplate($nodeTemplate, 'bar');
	}


	/**
	 * @test
	 */
	public function getPrimaryChildNodeReturnsTheFirstChildNode() {
		$mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$mockNodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');


		$node = new \TYPO3\TYPO3CR\Domain\Model\Node($mockNodeData, $mockContext);

		$mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
		$this->inject($node, 'nodeDataRepository', $mockNodeDataRepository);

		$expectedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNodeDataRepository->expects($this->once())->method('findFirstByParentAndNodeTypeInContext')->with('/foo/bar', NULL, $mockContext)->will($this->returnValue($expectedNode));

		$primaryChildNode = $node->getPrimaryChildNode();

		$this->assertSame($expectedNode, $primaryChildNode);
	}

}