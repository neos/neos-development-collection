<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Tests\Functional\AbstractNodeTest;

/**
 * Functional test case which tests FlowQuery ClosestOperation
 */
class ClosestOperationTest extends AbstractNodeTest {

	/**
	 * @return array
	 */
	public function closestOperationDataProvider() {
		return array(
			array(
				'currentNodePath' => '/b/b1/b1a',
				'nodeTypeFilter' => '[instanceof TYPO3.TYPO3CR.Testing:NodeType]',
				'expectedNodePath' => '/b/b1'
			),
			array(
				'currentNodePath' => '/b/b1/b1a',
				'nodeTypeFilter' => 'InvalidFilter',
				'expectedNodePath' => NULL
			),
			array(
				'currentNodePath' => '/b/b3/b3b',
				'nodeTypeFilter' => '[instanceof TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodes]',
				'expectedNodePath' => '/b/b3'
			),
			array(
				'currentNodePath' => '/b/b1/b1a',
				'nodeTypeFilter' => '[instanceof TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodes]',
				'expectedNodePath' => NULL
			),
			array(
				'currentNodePath' => '/b/b1',
				'nodeTypeFilter' => '[instanceof TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodes]',
				'expectedNodePath' => NULL
			),
			array(
				'currentNodePath' => '/b/b3/b3a',
				'nodeTypeFilter' => '[instanceof TYPO3.TYPO3CR.Testing:NodeType]',
				'expectedNodePath' => '/b/b3/b3a'
			)
		);
	}

	/**
	 * Tests on a tree:
	 *
	 * a
	 *   a1
	 *   a2
	 * b (TestingNodeType)
	 *   b1 (TestingNodeType)
	 *     b1a
	 *   b2
	 *   b3 (TestingNodeTypeWithSubnodes)
	 *     b3a (TestingNodeType)
	 *     b3b
	 *
	 * @test
	 * @dataProvider closestOperationDataProvider
	 */
	public function closestOperationTests($currentNodePath, $nodeTypeFilter, $expectedNodePath) {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$testNodeType1 = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType');
		$testNodeType2 = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodes');

		$rootNode = $this->node->getNode('/');
		$nodeA = $rootNode->createNode('a');
		$nodeA->createNode('a1');
		$nodeA->createNode('a2');
		$nodeB = $rootNode->createNode('b', $testNodeType1);
		$nodeB1 = $nodeB->createNode('b1', $testNodeType1);
		$nodeB1->createNode('b1a');
		$nodeB->createNode('b2');
		$nodeB3 = $nodeB->createNode('b3', $testNodeType2);
		$nodeB3->createNode('b3a', $testNodeType1);
		$nodeB3->createNode('b3b');

		$currentNode = $rootNode->getNode($currentNodePath);
		$q = new FlowQuery(array($currentNode));
		$actualNode = $q->closest($nodeTypeFilter)->get(0);

		if ($expectedNodePath === NULL) {
			if ($actualNode !== NULL) {
				$this->fail('Expected resulting node to be NULL');
			}
			$this->assertNull($actualNode);
		} else {
			$this->assertSame($expectedNodePath, $actualNode->getPath());
		}
	}
}