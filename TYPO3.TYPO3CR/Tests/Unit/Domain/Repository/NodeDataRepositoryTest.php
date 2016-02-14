<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

class NodeDataRepositoryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    protected function setUp()
    {
        $mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

        $this->nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('getNodeDataForParentAndNodeType', 'filterNodesOverlaidInBaseWorkspace', 'getNodeTypeFilterConstraintsForDql'));
        $this->nodeDataRepository->expects($this->any())->method('filterNodesOverlaidInBaseWorkspace')->will($this->returnCallback(function (array $foundNodes, Workspace $baseWorkspace, $dimensions) {
            return $foundNodes;
        }));

            // The repository needs an explicit entity class name because of the generated mock class name
        $this->inject($this->nodeDataRepository, 'entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
        $this->inject($this->nodeDataRepository, 'persistenceManager', $mockPersistenceManager);
    }

    /**
     * @test
     */
    public function findOneByPathFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo'));

        $this->nodeDataRepository->add($nodeData);

        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));

        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

        $this->assertSame($nodeData, $result);
    }

    /**
     * @test
     */
    public function findOneByPathFindsRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo'));

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));

        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function findOneByIdentifierFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));

        $this->nodeDataRepository->add($nodeData);

        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));

        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

        $this->assertSame($nodeData, $result);
    }

    /**
     * @test
     */
    public function findOneByIdentifierFindsRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));

        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function findByParentAndNodeTypeRecursivelyCallsGetNodeDataForParentAndNodeTypeWithRecursiveFlag()
    {
        $parentPath = 'some/parent/path';
        $nodeTypeFilter = 'Some.Package:SomeNodeType';
        $mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));
        $removedNodesFlag = true;
        $recursiveFlag = true;

        $this->nodeDataRepository->expects($this->once())->method('getNodeDataForParentAndNodeType')->with($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, $removedNodesFlag, $recursiveFlag)->will($this->returnValue(array()));
        $this->nodeDataRepository->expects($this->once())->method('getNodeTypeFilterConstraintsForDql')->with($nodeTypeFilter)->will($this->returnValue(array('excludeNodeTypes' => array(), 'includeNodeTypes' => array($nodeTypeFilter))));

        $this->nodeDataRepository->findByParentAndNodeTypeRecursively($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, true);
    }

    /**
     * @test
     */
    public function findByParentAndNodeTypeIncludesAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));
        $nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
        $nodeData->expects($this->any())->method('getDepth')->will($this->returnValue(2));

        $this->nodeDataRepository->add($nodeData);

        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));

        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $this->nodeDataRepository->expects($this->any())->method('getNodeDataForParentAndNodeType')->will($this->returnValue(array()));
        $this->nodeDataRepository->expects($this->once())->method('getNodeTypeFilterConstraintsForDql')->will($this->returnValue(array('excludeNodeTypes' => array(), 'includeNodeTypes' => array())));

        $result = $this->nodeDataRepository->findByParentAndNodeType('/foo', null, $liveWorkspace, $dimensions);

        $this->assertCount(1, $result);

        $fetchedNodeData = reset($result);

        $this->assertSame($nodeData, $fetchedNodeData);
    }

    /**
     * @test
     */
    public function findByParentAndNodeTypeRemovesRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getIdentifier')->will($this->returnValue('abcd-efgh-ijkl-mnop'));
        $nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
        $nodeData->expects($this->any())->method('getDepth')->will($this->returnValue(2));

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = array('persona' => array('everybody'), 'language' => array('de_DE', 'mul_ZZ'));

        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $this->nodeDataRepository->expects($this->any())->method('getNodeDataForParentAndNodeType')->will($this->returnValue(array(
            'abcd-efgh-ijkl-mnop' => $nodeData
        )));

        $result = $this->nodeDataRepository->findByParentAndNodeType('/foo', null, $liveWorkspace, $dimensions);

        $this->assertCount(0, $result);
    }
}
