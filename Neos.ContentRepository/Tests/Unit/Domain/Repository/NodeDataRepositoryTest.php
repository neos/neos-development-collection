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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var \Neos\ContentRepository\Domain\Repository\NodeDataRepository|MockObject
     */
    protected $nodeDataRepository;

    /**
     * Mocks the getResult method of \Doctrine\ORM\Query, which cannot be mocked for real, since it is final.
     *
     * @var MockObject
     */
    protected $mockQuery;

    /**
     * @var QueryBuilder|MockObject
     */
    protected $mockQueryBuilder;

    public function setUp(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->mockQuery = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();

        $this->mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->mockQueryBuilder->expects(self::any())->method('getQuery')->willReturn($this->mockQuery);

        $this->nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->addMethods(['filterNodesOverlaidInBaseWorkspace'])->onlyMethods(['getNodeDataForParentAndNodeType', 'getNodeTypeFilterConstraintsForDql', 'createQueryBuilder', 'addPathConstraintToQueryBuilder', 'filterNodeDataByBestMatchInContext'])->getMock();
        $this->nodeDataRepository->expects(self::any())->method('filterNodesOverlaidInBaseWorkspace')->willReturnCallback(function (array $foundNodes, Workspace $baseWorkspace, $dimensions) {
            return $foundNodes;
        });
        $this->nodeDataRepository->expects(self::any())->method('createQueryBuilder')->willReturn($this->mockQueryBuilder);
        $this->nodeDataRepository->expects(self::any())->method('filterNodeDataByBestMatchInContext')->willReturnArgument(0);

        // The repository needs an explicit entity class name because of the generated mock class name
        $this->inject($this->nodeDataRepository, 'entityClassName', NodeData::class);
        $this->inject($this->nodeDataRepository, 'persistenceManager', $mockPersistenceManager);
    }

    #[Test]
    public function findOneByPathFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');
        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getPath')->willReturn('/foo');
        $nodeData->expects(self::any())->method('getWorkspace')->willReturn($liveWorkspace);
        $nodeData->expects(self::any())->method('getDimensionValues')->willReturn($dimensions);
        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->willReturn(true);

        $this->mockQuery->expects(self::any())->method('getResult')->willReturn([]);

        $this->nodeDataRepository->add($nodeData);

        $result = $this->nodeDataRepository->findOneByPath('/foo', $liveWorkspace, $dimensions);

        self::assertSame($nodeData, $result);
    }

    #[Test]
    public function findOneByIdentifierFindsAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->willReturn('abcd-efgh-ijkl-mnop');

        $this->nodeDataRepository->add($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->willReturn(true);

        $result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

        self::assertSame($nodeData, $result);
    }

    #[Test]
    public function findOneByIdentifierFindsRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->willReturn('abcd-efgh-ijkl-mnop');

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->willReturn(true);

        $result = $this->nodeDataRepository->findOneByIdentifier('abcd-efgh-ijkl-mnop', $liveWorkspace, $dimensions);

        self::assertNull($result);
    }

    #[Test]
    public function findByParentAndNodeTypeRecursivelyCallsGetNodeDataForParentAndNodeTypeWithRecursiveFlag()
    {
        $parentPath = 'some/parent/path';
        $nodeTypeFilter = 'Some.Package:SomeNodeType';
        $mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];
        $removedNodesFlag = true;
        $recursiveFlag = true;

        $this->nodeDataRepository->expects(self::once())->method('getNodeDataForParentAndNodeType')->with($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, $removedNodesFlag, $recursiveFlag)->willReturn([]);
        $this->nodeDataRepository->expects(self::once())->method('getNodeTypeFilterConstraintsForDql')->with($nodeTypeFilter)->willReturn(['excludeNodeTypes' => [], 'includeNodeTypes' => [$nodeTypeFilter]]);

        $this->nodeDataRepository->findByParentAndNodeTypeRecursively($parentPath, $nodeTypeFilter, $mockWorkspace, $dimensions, true);
    }

    #[Test]
    public function findByParentAndNodeTypeIncludesAddedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->willReturn('abcd-efgh-ijkl-mnop');
        $nodeData->expects(self::any())->method('getPath')->willReturn('/foo/bar');
        $nodeData->expects(self::any())->method('getDepth')->willReturn(2);

        $this->nodeDataRepository->add($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->willReturn(true);

        $this->nodeDataRepository->expects(self::any())->method('getNodeDataForParentAndNodeType')->willReturn([]);
        $this->nodeDataRepository->expects(self::once())->method('getNodeTypeFilterConstraintsForDql')->willReturn(['excludeNodeTypes' => [], 'includeNodeTypes' => []]);

        $result = $this->nodeDataRepository->findByParentAndNodeType('/foo', null, $liveWorkspace, $dimensions);

        self::assertCount(1, $result);

        $fetchedNodeData = reset($result);

        self::assertSame($nodeData, $fetchedNodeData);
    }

    #[Test]
    public function findByParentAndNodeTypeRemovesRemovedNodeInRepositoryAndRespectsWorkspaceAndDimensions()
    {
        $liveWorkspace = new Workspace('live');

        $nodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $nodeData->expects(self::any())->method('getIdentifier')->willReturn('abcd-efgh-ijkl-mnop');
        $nodeData->expects(self::any())->method('getPath')->willReturn('/foo/bar');
        $nodeData->expects(self::any())->method('getDepth')->willReturn(2);

        $this->nodeDataRepository->remove($nodeData);

        $dimensions = ['persona' => ['everybody'], 'language' => ['de_DE', 'mul_ZZ']];

        $nodeData->expects(self::atLeastOnce())->method('matchesWorkspaceAndDimensions')->with($liveWorkspace, $dimensions)->willReturn(true);

        $this->nodeDataRepository->expects(self::any())->method('getNodeDataForParentAndNodeType')->willReturn([
            'abcd-efgh-ijkl-mnop' => $nodeData
        ]);

        $result = $this->nodeDataRepository->findByParentAndNodeType('/foo', null, $liveWorkspace, $dimensions);

        self::assertCount(0, $result);
    }
}
