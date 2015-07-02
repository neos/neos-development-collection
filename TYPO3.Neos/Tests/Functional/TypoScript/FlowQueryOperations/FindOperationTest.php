<?php
namespace TYPO3\Neos\Tests\Functional\TypoScript\FlowQueryOperations;

	/*                                                                        *
	 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
	 *                                                                        *
	 * It is free software; you can redistribute it and/or modify it under    *
	 * the terms of the GNU General Public License, either version 3 of the   *
	 * License, or (at your option) any later version.                        *
	 *                                                                        *
	 * The TYPO3 project - inspiring people to share!                         *
	 *                                                                        */
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Neos\Tests\Functional\AbstractNodeTest;

/**
 * Functional test case which tests FlowQuery FindOperation
 */
class FindOperationTest extends AbstractNodeTest {

	/**
	 * @test
	 * @expectedException \TYPO3\Eel\FlowQuery\FlowQueryException
	 */
	public function findByNodeIdentifierThrowsExceptionOnInvalidIdentifier() {
		$q = new FlowQuery(array($this->node));
		$q->find('#test')->get(0);
	}

	/**
	 * @test
	 */
	public function findByNodeIdentifierReturnsCorrectNodeInContext() {
		$q = new FlowQuery(array($this->node));
		$foundNode = $q->find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')->get(0);
		$this->assertSame($this->node->getNode('about-us'), $foundNode);

		$testContext = $this->contextFactory->create(array('workspaceName' => 'test'));

		$testNode = $testContext->getNode('/sites/example/home');
		$testQ = new FlowQuery(array($testNode));
		$testFoundNode = $testQ->find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')->get(0);
		$this->assertSame($testNode->getNode('about-us'), $testFoundNode);

		$this->assertNotSame($foundNode, $testFoundNode);
	}

	/**
	 * @test
	 */
	public function findByNodeWithInstanceofFilterReturnsMatchingNodesRecursively() {
		$q = new FlowQuery(array($this->node));
		$foundNodes = $q->find('[instanceof TYPO3.Neos.NodeTypes:Text]')->get();
		$this->assertGreaterThan(0, count($foundNodes));
		foreach ($foundNodes as $foundNode) {
			$this->assertSame($foundNode->getNodeType()->getName(), 'TYPO3.Neos.NodeTypes:Text');
		}
	}

	/**
	 * @test
	 */
	public function findByNodeWithMultipleInstanceofFilterReturnsMatchingNodesRecursively() {
		$q = new FlowQuery(array($this->node));
		$foundNodes = $q->find('[instanceof TYPO3.Neos.NodeTypes:Text],[instanceof TYPO3.Neos.NodeTypes:Page]')->get();
		$this->assertGreaterThan(0, count($foundNodes));
		$foundNodeTypes = array();
		foreach ($foundNodes as $foundNode) {
			$nodeType = $foundNode->getNodeType()->getName();
			if (!in_array($nodeType, $foundNodeTypes)) {
				$foundNodeTypes[] = $nodeType;
			}
		}
		sort($foundNodeTypes);
		$this->assertSame($foundNodeTypes, array('TYPO3.Neos.NodeTypes:Page', 'TYPO3.Neos.NodeTypes:Text'));
	}

	/**
	 * @test
	 */
	public function findByNodeWithPathReturnsCorrectNode() {
		$q = new FlowQuery(array($this->node));
		$foundNodes = $q->find('/sites/example/home/main/dummy42a')->get();
		$this->assertEquals(1, count($foundNodes));
		$foundNode = $foundNodes[0];
		$this->assertSame('b1e0e78d-04f3-8fc3-e3d1-e2399f831312', $foundNode->getIdentifier());
	}

	/**
	 * @test
	 */
	public function findByNodeWithPathReturnsEmptyArrayIfNotFound() {
		$q = new FlowQuery(array($this->node));
		$foundNodes = $q->find('/sites/example/home/main/limbo')->get();
		$this->assertEmpty($foundNodes);
	}

	/**
	 * @test
	 */
	public function findOperationEvaluatesWithEmptyContext() {
		$q = new FlowQuery(array());
		$foundNodes = $q->find('/sites/example/home/main/limbo')->get();
		$this->assertEmpty($foundNodes);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Eel\FlowQuery\FlowQueryException
	 */
	public function findOperationThrowsExceptionOnAtLeastOneInvalidContext() {
		$q = new FlowQuery(array($this->node, '1'));
		$q->find('/sites/example/home/main/limbo')->get();
	}

	/**
	 * @test
	 */
	public function findByMultipleNodesReturnsMatchingNodesForAllNodes() {
		$testContext = $this->contextFactory->create(array('workspaceName' => 'test'));
		$testNodeA = $testContext->getNode('/sites/example/home/main/dummy44');
		$testNodeB = $testContext->getNode('/sites/example/home/main/dummy45');
		$q = new FlowQuery(array($testNodeA, $testNodeB));

		$foundNodes = $q->find('[instanceof TYPO3.Neos.NodeTypes:Headline],[instanceof Acme.Demo:ListItem]')->get();
		$this->assertGreaterThan(0, count($foundNodes));
		$foundChildrenOfA = FALSE;
		$foundChildrenOfB = FALSE;

		foreach ($foundNodes as $foundNode) {
			if (strpos($foundNode->getPath(), $testNodeA->getPath()) === 0 && $foundNode->getNodeType()->getName() === 'TYPO3.Neos.NodeTypes:Headline') {
				$foundChildrenOfA = TRUE;
			}
			elseif (strpos($foundNode->getPath(), $testNodeB->getPath()) === 0 && $foundNode->getNodeType()->getName() === 'Acme.Demo:ListItem') {
				$foundChildrenOfB = TRUE;
			}
		}

		$this->assertTrue($foundChildrenOfA);
		$this->assertTrue($foundChildrenOfB);
	}
}
