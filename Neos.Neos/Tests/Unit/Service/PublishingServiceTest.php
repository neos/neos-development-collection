<?php
namespace Neos\Neos\Tests\Unit\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Site;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;

/**
 * Test case for the Workspace PublishingService
 */
class PublishingServiceTest extends UnitTestCase
{
    /**
     * @var PublishingService
     */
    protected $publishingService;

    /**
     * @var WorkspaceRepository
     */
    protected $mockWorkspaceRepository;

    /**
     * @var NodeDataRepository
     */
    protected $mockNodeDataRepository;

    /**
     * @var NodeFactory
     */
    protected $mockNodeFactory;

    /**
     * @var ContextFactoryInterface
     */
    protected $mockContextFactory;

    /**
     * @var Workspace
     */
    protected $mockWorkspace;

    /**
     * @var Workspace
     */
    protected $mockBaseWorkspace;

    /**
     * @var QueryResultInterface
     */
    protected $mockQueryResult;

    /**
     * @var Site
     */
    protected $mockSite;
    /**

     * @var ContentDimensionPresetSourceInterface
     */
    protected $mockContentDimensionPresetSource;

    public function setUp(): void
    {
        $this->publishingService = new PublishingService();

        $this->mockWorkspaceRepository = $this->getMockBuilder(WorkspaceRepository::class)->disableOriginalConstructor()->setMethods(['findOneByName'])->getMock();
        $this->inject($this->publishingService, 'workspaceRepository', $this->mockWorkspaceRepository);

        $this->mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['findByWorkspace'])->getMock();
        $this->inject($this->publishingService, 'nodeDataRepository', $this->mockNodeDataRepository);

        $this->mockNodeFactory = $this->getMockBuilder(NodeFactory::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'nodeFactory', $this->mockNodeFactory);

        $this->mockContextFactory = $this->getMockBuilder(ContextFactoryInterface::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'contextFactory', $this->mockContextFactory);

        $this->mockBaseWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->mockBaseWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));
        $this->mockBaseWorkspace->expects(self::any())->method('getBaseWorkspace')->will(self::returnValue(null));

        $this->mockContentDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockContentDimensionPresetSource->expects(self::any())->method('findPresetsByTargetValues')->will($this->returnArgument(0));
        $this->inject($this->publishingService, 'contentDimensionPresetSource', $this->mockContentDimensionPresetSource);

        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('workspace-name'));
        $this->mockWorkspace->expects(self::any())->method('getBaseWorkspace')->will(self::returnValue($this->mockBaseWorkspace));
    }

    /**
     * @test
     */
    public function getUnpublishedNodesReturnsAnEmptyArrayIfThereAreNoNodesInTheGivenWorkspace()
    {
        $this->mockNodeDataRepository->expects(self::atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->will(self::returnValue([]));

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        self::assertSame($actualResult, []);
    }

    /**
     * @test
     */
    public function getUnpublishedNodesReturnsANodeInstanceForEveryNodeInTheGivenWorkspace()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $expectedContextProperties = [
            'workspaceName' => $this->mockWorkspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => []
        ];
        $this->mockContextFactory->expects(self::any())->method('create')->with($expectedContextProperties)->will(self::returnValue($mockContext));

        $mockNodeData1 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects(self::any())->method('getDimensionValues')->will(self::returnValue([]));
        $mockNodeData2->expects(self::any())->method('getDimensionValues')->will(self::returnValue([]));

        $mockNode1 = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockNode2 = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNode1->expects(self::any())->method('getNodeData')->will(self::returnValue($mockNodeData1));
        $mockNode1->expects(self::any())->method('getPath')->will(self::returnValue('/node1'));
        $mockNode2->expects(self::any())->method('getNodeData')->will(self::returnValue($mockNodeData2));
        $mockNode2->expects(self::any())->method('getPath')->will(self::returnValue('/node2'));

        $this->mockNodeFactory->expects(self::atLeast(2))
            ->method('createFromNodeData')
            ->withConsecutive([$mockNodeData1, $mockContext], [$mockNodeData2, $mockContext])
            ->willReturnOnConsecutiveCalls($mockNode1, $mockNode2);

        $this->mockNodeDataRepository->expects(self::atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->will(self::returnValue([$mockNodeData1, $mockNodeData2]));

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        self::assertSame($actualResult, [$mockNode2, $mockNode1]);
    }

    /**
     * @test
     */
    public function getUnpublishedNodesDoesNotReturnInvalidNodes()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $expectedContextProperties = [
            'workspaceName' => $this->mockWorkspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => []
        ];
        $this->mockContextFactory->expects(self::any())->method('create')->with($expectedContextProperties)->will(self::returnValue($mockContext));

        $mockNodeData1 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects(self::any())->method('getDimensionValues')->will(self::returnValue([]));
        $mockNodeData2->expects(self::any())->method('getDimensionValues')->will(self::returnValue([]));

        $mockNode1 = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNode1->expects(self::any())->method('getNodeData')->will(self::returnValue($mockNodeData1));
        $mockNode1->expects(self::any())->method('getPath')->will(self::returnValue('/node1'));

        $this->mockNodeFactory->expects(self::atLeast(2))
            ->method('createFromNodeData')
            ->withConsecutive([$mockNodeData1, $mockContext], [$mockNodeData2, $mockContext])
            ->willReturnOnConsecutiveCalls($mockNode1, null);

        $this->mockNodeDataRepository->expects(self::atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->will(self::returnValue([$mockNodeData1, $mockNodeData2]));

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        self::assertSame($actualResult, [$mockNode1]);
    }

    /**
     * @test
     */
    public function getUnpublishedNodesCountReturnsTheNumberOfNodesInTheGivenWorkspaceMinusItsRootNode()
    {
        $this->mockWorkspace->expects(self::atLeastOnce())->method('getNodeCount')->will(self::returnValue(123));
        $actualResult = $this->publishingService->getUnpublishedNodesCount($this->mockWorkspace);
        $expectedResult = 122;
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheGivenNodeFromItsWorkspaceToTheSpecifiedTargetWorkspace()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode], $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheGivenNodeToItsBaseWorkspaceIfNoTargetWorkspaceIsSpecified()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode], $this->mockBaseWorkspace);
        $this->publishingService->publishNode($mockNode);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeIsADocument()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode->expects(self::any())->method('getChildNodes')->with('!Neos.Neos:Document')->will(self::returnValue([]));

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects(self::atLeastOnce())->method('isOfType')->with('Neos.Neos:Document')->will(self::returnValue(true));
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));
        $mockNode->expects(self::atLeastOnce())->method('getChildNodes')->with('!Neos.Neos:Document')->will(self::returnValue([$mockChildNode]));

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode, $mockChildNode], $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }


    /**
     * @test
     */
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeTypeHasChildNodes()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode->expects(self::any())->method('getChildNodes')->with('!Neos.Neos:Document')->will(self::returnValue([]));

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->setMethods(['hasConfiguration', 'isOfType'])->getMock();
        $mockNodeType->expects(self::atLeastOnce())->method('hasConfiguration')->with('childNodes')->will(self::returnValue(true));
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));
        $mockNode->expects(self::atLeastOnce())->method('getChildNodes')->with('!Neos.Neos:Document')->will(self::returnValue([$mockChildNode]));

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode, $mockChildNode], $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }
}
