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
		$newNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');
		$nodeTemplate = new \TYPO3\TYPO3CR\Domain\Model\NodeTemplate();

		$context->expects($this->any())->method('getWorkspace')->will($this->returnValue($workspace));

		$nodeFactory = $this->getMock('TYPO3\TYPO3CR\Domain\Factory\NodeFactory');

		$parentNode = new \TYPO3\TYPO3CR\Domain\Model\Node($parentNodeData, $context);

		$this->inject($parentNode, 'nodeFactory', $nodeFactory);

		$parentNodeData->expects($this->atLeastOnce())->method('createNodeFromTemplate')->with($nodeTemplate, 'bar', $workspace)->will($this->returnValue($newNodeData));
		$nodeFactory->expects($this->atLeastOnce())->method('createFromNodeData')->with($newNodeData, $context)->will($this->returnValue($newNode));

		$parentNode->createNodeFromTemplate($nodeTemplate, 'bar');
	}

}