<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;

class NodeDataRepositoryTest extends UnitTestCase
{
    /**
     * @var \Neos\ContentRepository\Domain\Repository\NodeDataRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodeDataRepository;

    /**
     * Mocks the getResult method of \Doctrine\ORM\Query, which cannot be mocked for real, since it is final.
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockQuery;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockQueryBuilder;

    protected function setUp()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->mockQuery = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();

        $this->mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->mockQueryBuilder->expects($this->any())->method('getQuery')->will($this->returnValue($this->mockQuery));

        $this->nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->setMethods(array('getNodeDataForParentAndNodeType', 'filterNodesOverlaidInBaseWorkspace', 'getNodeTypeFilterConstraintsForDql', 'createQueryBuilder', 'addPathConstraintToQueryBuilder', 'filterNodeDataByBestMatchInContext'))->getMock();
        $this->nodeDataRepository->expects($this->any())->method('filterNodesOverlaidInBaseWorkspace')->will($this->returnCallback(function (array $foundNodes, Workspace $baseWorkspace, $dimensions) {
            return $foundNodes;
        }));
        $this->nodeDataRepository->expects($this->any())->method('createQueryBuilder')->will($this->returnValue($this->mockQueryBuilder));
        $this->nodeDataRepository->expects($this->any())->method('filterNodeDataByBestMatchInContext')->will($this->returnArgument(0));

        // The repository needs an explicit entity class name because of the generated mock class name
        $this->inject($this->nodeDataRepository, 'entityClassName', NodeData::class);
        $this->inject($this->nodeDataRepository, 'persistenceManager', $mockPersistenceManager);
    }

    /**
     * @test
     */
    public function findOneByPathFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');
        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo'));
        $nodeData->expects($this->any())->method('getWorkspace')->will($this->returnValue($liveWorkspace));
        $nodeData->expects($this->any())->method('getDimensionValues')->will($this->returnValue($dimensions));
        $nodeData->expects($this->atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will($this->returnValue(true));

        $this->mockQuery->expects($this->any())->method('getResult')->will($this->returnValue([]));

        $this->nodeDataRepository->add($nodeData);

        $result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

        $this->assertSame($nodeData, $result);
    }

    /**
     * @test
     */
    public function findOneByIdentifierFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
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

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
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
        $mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
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

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
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

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
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
