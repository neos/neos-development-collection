<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Service\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class NodePublishingDependencySolverTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $mockWorkspace;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected $mockContext;

	public function setUp() {
		$this->mockWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array('live'));
		$this->mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function sortNodesWithParentRelations() {
		$nodeService = $this->buildNodeMock('/sites/typo3cr/service');
		$nodeCompany = $this->buildNodeMock('/sites/typo3cr/company');
		$nodeAboutUs = $this->buildNodeMock('/sites/typo3cr/company/about-us');

		$unpublishedNodes = array($nodeAboutUs, $nodeService, $nodeCompany);

		$solver = new \TYPO3\TYPO3CR\Service\Utility\NodePublishingDependencySolver();
		$sortedNodes = $solver->sort($unpublishedNodes);

		$this->assertBeforeInArray($nodeCompany, $nodeAboutUs, $sortedNodes);
	}

	/**
	 * @test
	 */
	public function sortNodesWithMovedToRelations() {
		$nodeEnterprise = $this->buildNodeMock('/sites/typo3cr/enterprise');

		// "company" was moved to "enterprise"
		$nodeCompany = $this->buildNodeMock('/sites/typo3cr/company', $nodeEnterprise->getNodeData());
		$nodeAboutUs = $this->buildNodeMock('/sites/typo3cr/company/about-us');

		// "service" was moved to "company"
		$nodeService = $this->buildNodeMock('/sites/typo3cr/service', $nodeCompany->getNodeData());

		$unpublishedNodes = array($nodeAboutUs, $nodeService, $nodeCompany, $nodeEnterprise);

		$solver = new \TYPO3\TYPO3CR\Service\Utility\NodePublishingDependencySolver();
		$sortedNodes = $solver->sort($unpublishedNodes);

		$this->assertBeforeInArray($nodeEnterprise, $nodeCompany, $sortedNodes);
		$this->assertBeforeInArray($nodeCompany, $nodeAboutUs, $sortedNodes);
		$this->assertBeforeInArray($nodeCompany, $nodeService, $sortedNodes);
	}

	/**
	 * Build a mock Node for testing
	 *
	 * @param string $path
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $movedTo
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	protected function buildNodeMock($path, $movedTo = NULL) {
		$mockNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array($path, $this->mockWorkspace));
		$mockNodeData->expects($this->any())->method('getMovedTo')->will($this->returnValue($movedTo));
		$mockNodeData->expects($this->any())->method('getPath')->will($this->returnValue($path));
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Node', array(), array($mockNodeData, $this->mockContext));
		$mockNode->expects($this->any())->method('getNodeData')->will($this->returnValue($mockNodeData));
		$mockNode->expects($this->any())->method('getPath')->will($this->returnValue($path));
		$parentPath = substr($path, 0, strrpos($path, '/'));
		$mockNode->expects($this->any())->method('getParentPath')->will($this->returnValue($parentPath));

		return $mockNode;
	}

	/**
	 * Assert the element1 is before element2 in the given list of elements
	 *
	 * @param mixed $element1
	 * @param mixed $element2
	 * @param array $elements
	 * @return void
	 */
	protected function assertBeforeInArray($element1, $element2, array $elements) {
		$position1 = array_search($element1, $elements, TRUE);
		$position2 = array_search($element2, $elements, TRUE);
		if ($position1 === FALSE || $position2 === FALSE) {
			$this->fail('Element not found in list');
		}
		$this->assertLessThan($position2, $position1, 'Element order does not match');
	}
}
