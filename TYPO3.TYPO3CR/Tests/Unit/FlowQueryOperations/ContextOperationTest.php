<?php
namespace TYPO3\TYPO3CR\Tests\Unit\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Eel\FlowQueryOperations\ContextOperation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Testcase for the FlowQuery ContextOperation
 */
class ContextOperationTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var ContextOperation
	 */
	protected $operation;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $mockContextFactory;

	public function setUp() {
		$this->operation = new ContextOperation();
		$this->mockContextFactory = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$this->inject($this->operation, 'contextFactory', $this->mockContextFactory);
	}

	/**
	 * @test
	 */
	public function canEvaluateReturnsTrueIfNodeIsInContext() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$result = $this->operation->canEvaluate(array($mockNode));
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function evaluateCreatesModifiedContextFromFactoryUsingMergedProperties() {
		$suppliedContextProperties = array('infiniteImprobabilityDrive' => TRUE);
		$nodeContextProperties = array('infiniteImprobabilityDrive' => FALSE, 'autoRemoveUnsuitableContent' => TRUE);
		$expectedModifiedContextProperties = array('infiniteImprobabilityDrive' => TRUE, 'autoRemoveUnsuitableContent' => TRUE);

		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

		$modifiedNodeContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();

		$this->mockContextFactory->expects($this->atLeastOnce())->method('create')->with($expectedModifiedContextProperties)->will($this->returnValue($modifiedNodeContext));

		$this->operation->evaluate($mockFlowQuery, array($suppliedContextProperties));
	}

	/**
	 * @test
	 */
	public function evaluateGetsAndSetsNodesInContextFromModifiedContextByIdentifier() {
		$suppliedContextProperties = array('infiniteImprobabilityDrive' => TRUE);
		$nodeContextProperties = array('infiniteImprobabilityDrive' => FALSE, 'autoRemoveUnsuitableContent' => TRUE);
		$nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));
		$mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

		$modifiedNodeContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$nodeInModifiedContext = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$nodeInModifiedContext->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($modifiedNodeContext));

		$modifiedNodeContext->expects($this->once())->method('getNodeByIdentifier')->with($nodeIdentifier)->will($this->returnValue($nodeInModifiedContext));
		$mockFlowQuery->expects($this->atLeastOnce())->method('setContext')->with(array($nodeInModifiedContext));

		$this->operation->evaluate($mockFlowQuery, array($suppliedContextProperties));
	}

	/**
	 * @test
	 */
	public function evaluateSkipsNodesNotAvailableInModifiedContext() {
		$suppliedContextProperties = array('infiniteImprobabilityDrive' => TRUE);
		$nodeContextProperties = array('infiniteImprobabilityDrive' => FALSE, 'autoRemoveUnsuitableContent' => TRUE);
		$nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));
		$mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

		$modifiedNodeContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($modifiedNodeContext));

		$modifiedNodeContext->expects($this->once())->method('getNodeByIdentifier')->with($nodeIdentifier)->will($this->returnValue(NULL));
		$mockFlowQuery->expects($this->atLeastOnce())->method('setContext')->with(array());

		$this->operation->evaluate($mockFlowQuery, array($suppliedContextProperties));
	}

	/**
	 * @param NodeInterface $mockNode
	 * @param array $nodeContextProperties
	 * @return FlowQuery
	 */
	protected function buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties) {
		$mockNodeContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$mockNodeContext->expects($this->any())->method('getProperties')->will($this->returnValue($nodeContextProperties));

		$mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockNodeContext));

		$mockFlowQuery = $this->getMockBuilder('TYPO3\Eel\FlowQuery\FlowQuery')->disableOriginalConstructor()->getMock();
		$mockFlowQuery->expects($this->any())->method('getContext')->will($this->returnValue(array($mockNode)));
		return $mockFlowQuery;
	}

}
