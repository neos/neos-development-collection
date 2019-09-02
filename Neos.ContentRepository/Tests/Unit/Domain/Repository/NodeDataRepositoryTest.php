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
     * @var \Neos\ContentRepository\Domain\Repository\NodeDataRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $nodeDataRepository;

    /**
     * Mocks the getResult method of \Doctrine\ORM\Query, which cannot be mocked for real, since it is final.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockQuery;

    /**
     * @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockQueryBuilder;

    public function setUp(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->mockQuery = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();

        $this->mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->mockQueryBuilder->expects(self::any())->method('getQuery')->will(self::returnValue($this->mockQuery));

        $this->nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->setMethods(['getNodeDataForParentAndNodeType', 'filterNodesOverlaidInBaseWorkspace', 'getNodeTypeFilterConstraintsForDql', 'createQueryBuilder', 'addPathConstraintToQueryBuilder', 'filterNodeDataByBestMatchInContext'])->getMock();
        $this->nodeDataRepository->expects(self::any())->method('filterNodesOverlaidInBaseWorkspace')->will(self::returnCallback(function (array $foundNodes, Workspace $baseWorkspace, $dimensions) {
            return $foundNodes;
        }));
        $this->nodeDataRepository->expects(self::any())->method('createQueryBuilder')->will(self::returnValue($this->mockQueryBuilder));
        $this->nodeDataRepository->expects(self::any())->method('filterNodeDataByBestMatchInContext')->will($this->returnArgument(0));

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
        $nodeData->expects(self::any())->method('getPath')->will(self::returnValue('/foo'));
        $nodeData->expects(self::any())->method('getWorkspace')->will(self::returnValue($liveWorkspace));
        $nodeData->expects(self::any())->method('getDimensionValues')->will(self::returnValue($dimensions));
        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will(self::returnValue(true));

        $this->mockQuery->expects(self::any())->method('getResult')->will(self::returnValue([]));

        $this->nodeDataRepository->add($nodeData);

        $result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

        self::assertSame($nodeData, $result);
    }

    /**
     * @test
     */
    public function findOneByIdentifierFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->will(self::returnValue('abcd-efgh-ijkl-mnop'));

        $this->nodeDataRepository->add($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will(self::returnValue(true));

        $result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

        self::assertSame($nodeData, $result);
    }

    /**
     * @test
     */
    public function findOneByIdentifierFindsRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->will(self::returnValue('abcd-efgh-ijkl-mnop'));

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will(self::returnValue(true));

        $result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function findByParentAndNodeTypeRecursivelyCallsGetNodeDataForParentAndNodeTypeWithRecursiveFlag()
    {
        $parentPath = 'some/parent/path';
        $nodeTypeFilter = 'Some.Package:SomeNodeType';
        $mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];
        $removedNodesFlag = true;
        $recursiveFlag = true;

        $this->nodeDataRepository->expects(self::once())->method('getNodeDataForParentAndNodeType')->with($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, $removedNodesFlag, $recursiveFlag)->will(self::returnValue([]));
        $this->nodeDataRepository->expects(self::once())->method('getNodeTypeFilterConstraintsForDql')->with($nodeTypeFilter)->will(self::returnValue(['excludeNodeTypes' => [], 'includeNodeTypes' => [$nodeTypeFilter]]));

        $this->nodeDataRepository->findByParentAndNodeTypeRecursively($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, true);
    }

    /**
     * @test
     */
    public function findByParentAndNodeTypeIncludesAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->will(self::returnValue('abcd-efgh-ijkl-mnop'));
        $nodeData->expects(self::any())->method('getPath')->will(self::returnValue('/foo/bar'));
        $nodeData->expects(self::any())->method('getDepth')->will(self::returnValue(2));

        $this->nodeDataRepository->add($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will(self::returnValue(true));

        $this->nodeDataRepository->expects(self::any())->method('getNodeDataForParentAndNodeType')->will(self::returnValue([]));
        $this->nodeDataRepository->expects(self::once())->method('getNodeTypeFilterConstraintsForDql')->will(self::returnValue(['excludeNodeTypes' => [], 'includeNodeTypes' => []]));

        $result = $this->nodeDataRepository->findByParentAndNodeType('/foo', null, $liveWorkspace, $dimensions);

        self::assertCount(1, $result);

        $fetchedNodeData = reset($result);

        self::assertSame($nodeData, $fetchedNodeData);
    }

    /**
     * @test
     */
    public function findByParentAndNodeTypeRemovesRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->will(self::returnValue('abcd-efgh-ijkl-mnop'));
        $nodeData->expects(self::any())->method('getPath')->will(self::returnValue('/foo/bar'));
        $nodeData->expects(self::any())->method('getDepth')->will(self::returnValue(2));

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->will(self::returnValue(true));

        $this->nodeDataRepository->expects(self::any())->method('getNodeDataForParentAndNodeType')->will(self::returnValue([
            'abcd-efgh-ijkl-mnop' => $nodeData
        ]));

        $result = $this->nodeDataRepository->findByParentAndNodeType('/foo', null, $liveWorkspace, $dimensions);

        self::assertCount(0, $result);
    }
}
