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
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Service\PublishingService;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

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

        $this->mockWorkspaceRepository = $this->getMockBuilder(WorkspaceRepository::class)->disableOriginalConstructor()->addMethods(['findOneByName'])->getMock();
        $this->inject($this->publishingService, 'workspaceRepository', $this->mockWorkspaceRepository);

        $this->mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->onlyMethods(['findByWorkspace'])->getMock();
        $this->inject($this->publishingService, 'nodeDataRepository', $this->mockNodeDataRepository);

        $this->mockNodeFactory = $this->getMockBuilder(NodeFactory::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'nodeFactory', $this->mockNodeFactory);

        $this->mockContextFactory = $this->getMockBuilder(ContextFactoryInterface::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'contextFactory', $this->mockContextFactory);

        $this->mockBaseWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->mockBaseWorkspace->expects(self::any())->method('getName')->willReturn('live');
        $this->mockBaseWorkspace->expects(self::any())->method('getBaseWorkspace')->willReturn(null);

        $this->mockContentDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockContentDimensionPresetSource->expects(self::any())->method('findPresetsByTargetValues')->willReturnArgument(0);
        $this->inject($this->publishingService, 'contentDimensionPresetSource', $this->mockContentDimensionPresetSource);

        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->mockWorkspace->expects(self::any())->method('getName')->willReturn('workspace-name');
        $this->mockWorkspace->expects(self::any())->method('getBaseWorkspace')->willReturn($this->mockBaseWorkspace);
    }

    #[Test]
    public function getUnpublishedNodesReturnsAnEmptyArrayIfThereAreNoNodesInTheGivenWorkspace()
    {
        $this->mockNodeDataRepository->expects(self::atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->willReturn([]);

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        self::assertSame($actualResult, []);
    }

    #[Test]
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
        $this->mockContextFactory->expects(self::any())->method('create')->with($expectedContextProperties)->willReturn($mockContext);

        $mockNodeData1 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects(self::any())->method('getDimensionValues')->willReturn([]);
        $mockNodeData2->expects(self::any())->method('getDimensionValues')->willReturn([]);

        $mockNode1 = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockNode2 = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNode1->expects(self::any())->method('getNodeData')->willReturn($mockNodeData1);
        $mockNode1->expects(self::any())->method('getPath')->willReturn('/node1');
        $mockNode2->expects(self::any())->method('getNodeData')->willReturn($mockNodeData2);
        $mockNode2->expects(self::any())->method('getPath')->willReturn('/node2');
        $matcher = self::atLeast(2);

        $this->mockNodeFactory->expects($matcher)
            ->method('createFromNodeData')->willReturnCallback(function (...$parameters) use ($matcher, $mockNodeData1, $mockContext, $mockNodeData2, $mockNode1, $mockNode2) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($mockNodeData1, $parameters[0]);
                $this->assertSame($mockContext, $parameters[1]);
                return $mockNode1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($mockNodeData2, $parameters[0]);
                $this->assertSame($mockContext, $parameters[1]);
                return $mockNode2;
            }
        });

        $this->mockNodeDataRepository->expects(self::atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->willReturn([$mockNodeData1, $mockNodeData2]);

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        self::assertSame($actualResult, [$mockNode2, $mockNode1]);
    }

    #[Test]
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
        $this->mockContextFactory->expects(self::any())->method('create')->with($expectedContextProperties)->willReturn($mockContext);

        $mockNodeData1 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects(self::any())->method('getDimensionValues')->willReturn([]);
        $mockNodeData2->expects(self::any())->method('getDimensionValues')->willReturn([]);

        $mockNode1 = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNode1->expects(self::any())->method('getNodeData')->willReturn($mockNodeData1);
        $mockNode1->expects(self::any())->method('getPath')->willReturn('/node1');
        $matcher = self::atLeast(2);

        $this->mockNodeFactory->expects($matcher)
            ->method('createFromNodeData')->willReturnCallback(function (...$parameters) use ($matcher, $mockNodeData1, $mockContext, $mockNodeData2, $mockNode1) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($mockNodeData1, $parameters[0]);
                $this->assertSame($mockContext, $parameters[1]);
                return $mockNode1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($mockNodeData2, $parameters[0]);
                $this->assertSame($mockContext, $parameters[1]);
                return null;
            }
        });

        $this->mockNodeDataRepository->expects(self::atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->willReturn([$mockNodeData1, $mockNodeData2]);

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        self::assertSame($actualResult, [$mockNode1]);
    }

    #[Test]
    public function getUnpublishedNodesCountReturnsTheNumberOfNodesInTheGivenWorkspaceMinusItsRootNode()
    {
        $this->mockWorkspace->expects(self::atLeastOnce())->method('getNodeCount')->willReturn(123);
        $actualResult = $this->publishingService->getUnpublishedNodesCount($this->mockWorkspace);
        $expectedResult = 122;
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function publishNodePublishesTheGivenNodeFromItsWorkspaceToTheSpecifiedTargetWorkspace()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->willReturn($mockNodeType);

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->willReturn($this->mockWorkspace);

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode], $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }

    #[Test]
    public function publishNodePublishesTheGivenNodeToItsBaseWorkspaceIfNoTargetWorkspaceIsSpecified()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->willReturn($mockNodeType);

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->willReturn($this->mockWorkspace);

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode], $this->mockBaseWorkspace);
        $this->publishingService->publishNode($mockNode);
    }

    #[Test]
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeIsADocument()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode->expects(self::any())->method('getChildNodes')->with('!Neos.Neos:Document')->willReturn([]);

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects(self::atLeastOnce())->method('isOfType')->with('Neos.Neos:Document')->willReturn(true);
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->willReturn($mockNodeType);

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->willReturn($this->mockWorkspace);
        $mockNode->expects(self::atLeastOnce())->method('getChildNodes')->with('!Neos.Neos:Document')->willReturn([$mockChildNode]);

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode, $mockChildNode], $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }


    #[Test]
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeTypeHasChildNodes()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode->expects(self::any())->method('getChildNodes')->with('!Neos.Neos:Document')->willReturn([]);

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->onlyMethods(['hasConfiguration', 'isOfType'])->getMock();
        $mockNodeType->expects(self::atLeastOnce())->method('hasConfiguration')->with('childNodes')->willReturn(true);
        $mockNode->expects(self::atLeastOnce())->method('getNodeType')->willReturn($mockNodeType);

        $mockNode->expects(self::atLeastOnce())->method('getWorkspace')->willReturn($this->mockWorkspace);
        $mockNode->expects(self::atLeastOnce())->method('getChildNodes')->with('!Neos.Neos:Document')->willReturn([$mockChildNode]);

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects(self::atLeastOnce())->method('publishNodes')->with([$mockNode, $mockChildNode], $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }
}
