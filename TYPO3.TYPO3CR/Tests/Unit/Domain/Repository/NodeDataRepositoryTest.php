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

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

class NodeDataRepositoryTest extends UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	protected function setUp() {
		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

		$this->nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('getNodeDataForParentAndNodeType', 'filterNodesOverlaidInBaseWorkspace'));
		$this->nodeDataRepository->expects($this->any())->method('filterNodesOverlaidInBaseWorkspace')->will($this->returnCallback(function(array $foundNodes, Workspace $baseWorkspace, $dimensions) {
			return $foundNodes;
		}));

			// The repository needs an explicit entity class name because of the generated mock class name
		$this->inject($this->nodeDataRepository, 'entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
		$this->inject($this->nodeDataRepository, 'persistenceManager', $mockPersistenceManager);
	}

	/**
	 * @test
	 */
	public function findOneByPathFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions() {
		$liveWorkspace = new Workspace('live');

		$nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo'));

		$this->nodeDataRepository->add($nodeData);

		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));

		$nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(TRUE));

		$result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

		$this->assertSame($nodeData, $result);
	}

	/**
	 * @test
	 */
	public function findOneByPathFindsRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions() {
		$liveWorkspace = new Workspace('live');

		$nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo'));

		$this->nodeDataRepository->remove($nodeData);

		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));

		$nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(TRUE));

		$result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

		$this->assertNull($result);
	}

	/**
	 * @test
	 */
	public function findOneByIdentifierFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions() {
		$liveWorkspace = new Workspace('live');

		$nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));

		$this->nodeDataRepository->add($nodeData);

		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));

		$nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(TRUE));

		$result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

		$this->assertSame($nodeData, $result);
	}

	/**
	 * @test
	 */
	public function findOneByIdentifierFindsRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions() {
		$liveWorkspace = new Workspace('live');

		$nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));

		$this->nodeDataRepository->remove($nodeData);

		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));

		$nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(TRUE));

		$result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

		$this->assertNull($result);
	}

	/**
	 * @test
	 */
	public function findByParentAndNodeTypeRecursivelyCallsGetNodeDataForParentAndNodeTypeWithRecursiveFlag() {
		$parentPath = 'some/parent/path';
		$nodeTypeFilter = 'Some.Package:SomeNodeType';
		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));
		$removedNodesFlag = TRUE;
		$recursiveFlag = TRUE;

		$this->nodeDataRepository->expects($this->once())->method('getNodeDataForParentAndNodeType')->with($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, $removedNodesFlag, $recursiveFlag)->will($this->returnValue(array()));

		$this->nodeDataRepository->findByParentAndNodeTypeRecursively($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, TRUE);

	}

	/**
	 * @test
	 */
	public function findByParentAndNodeTypeIncludesAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions() {
		$liveWorkspace = new Workspace('live');

		$nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));
		$nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
		$nodeData->expects($this->any())->method('getDepth')->will($this->returnValue(2));

		$this->nodeDataRepository->add($nodeData);

		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));

		$nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(TRUE));

		$this->nodeDataRepository->expects($this->any())->method('getNodeDataForParentAndNodeType')->will($this->returnValue(array()));

		$result = $this->nodeDataRepository->findByParentAndNodeType('/foo', NULL, $liveWorkspace, $dimensions);

		$this->assertCount(1, $result);

		$fetchedNodeData = reset($result);

		$this->assertSame($nodeData, $fetchedNodeData);
	}

	/**
	 * @test
	 */
	public function findByParentAndNodeTypeRemovesRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions() {
		$liveWorkspace = new Workspace('live');

		$nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));
		$nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
		$nodeData->expects($this->any())->method('getDepth')->will($this->returnValue(2));

		$this->nodeDataRepository->remove($nodeData);

		$dimensions = array('personas' => array('everybody'), 'locales' => array('de_DE', 'mul_ZZ'));

		$nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(TRUE));

		$this->nodeDataRepository->expects($this->any())->method('getNodeDataForParentAndNodeType')->will($this->returnValue(array(
			'abcd-efgh-ijkl-mnop' => $nodeData
		)));

		$result = $this->nodeDataRepository->findByParentAndNodeType('/foo', NULL, $liveWorkspace, $dimensions);

		$this->assertCount(0, $result);
	}

}
