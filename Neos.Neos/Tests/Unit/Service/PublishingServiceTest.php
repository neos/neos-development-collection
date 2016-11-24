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
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\PublishingService;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

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

    public function setUp()
    {
        $this->publishingService = new PublishingService();

        $this->mockWorkspaceRepository = $this->getMockBuilder(WorkspaceRepository::class)->disableOriginalConstructor()->setMethods(array('findOneByName'))->getMock();
        $this->inject($this->publishingService, 'workspaceRepository', $this->mockWorkspaceRepository);

        $this->mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('findByWorkspace'))->getMock();
        $this->inject($this->publishingService, 'nodeDataRepository', $this->mockNodeDataRepository);

        $this->mockNodeFactory = $this->getMockBuilder(NodeFactory::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'nodeFactory', $this->mockNodeFactory);

        $this->mockContextFactory = $this->getMockBuilder(ContextFactoryInterface::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'contextFactory', $this->mockContextFactory);
        
        $this->mockBaseWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->mockBaseWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));
        $this->mockBaseWorkspace->expects($this->any())->method('getBaseWorkspace')->will($this->returnValue(null));

        $this->mockContentDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockContentDimensionPresetSource->expects($this->any())->method('findPresetsByTargetValues')->will($this->returnArgument(0));
        $this->inject($this->publishingService, 'contentDimensionPresetSource', $this->mockContentDimensionPresetSource);

        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('workspace-name'));
        $this->mockWorkspace->expects($this->any())->method('getBaseWorkspace')->will($this->returnValue($this->mockBaseWorkspace));
    }

    /**
     * @test
     */
    public function getUnpublishedNodesReturnsAnEmptyArrayIfThereAreNoNodesInTheGivenWorkspace()
    {
        $this->mockNodeDataRepository->expects($this->atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->will($this->returnValue(array()));

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        $this->assertSame($actualResult, array());
    }

    /**
     * @test
     */
    public function getUnpublishedNodesReturnsANodeInstanceForEveryNodeInTheGivenWorkspace()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $expectedContextProperties = array(
            'workspaceName' => $this->mockWorkspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => array()
        );
        $this->mockContextFactory->expects($this->any())->method('create')->with($expectedContextProperties)->will($this->returnValue($mockContext));

        $mockNodeData1 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));
        $mockNodeData2->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));

        $mockNode1 = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockNode2 = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNode1->expects($this->any())->method('getNodeData')->will($this->returnValue($mockNodeData1));
        $mockNode1->expects($this->any())->method('getPath')->will($this->returnValue('/node1'));
        $mockNode2->expects($this->any())->method('getNodeData')->will($this->returnValue($mockNodeData2));
        $mockNode2->expects($this->any())->method('getPath')->will($this->returnValue('/node2'));

        $this->mockNodeFactory->expects($this->at(0))->method('createFromNodeData')->with($mockNodeData1, $mockContext)->will($this->returnValue($mockNode1));
        $this->mockNodeFactory->expects($this->at(1))->method('createFromNodeData')->with($mockNodeData2, $mockContext)->will($this->returnValue($mockNode2));

        $this->mockNodeDataRepository->expects($this->atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->will($this->returnValue(array($mockNodeData1, $mockNodeData2)));

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        $this->assertSame($actualResult, array($mockNode2, $mockNode1));
    }

    /**
     * @test
     */
    public function getUnpublishedNodesDoesNotReturnInvalidNodes()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $expectedContextProperties = array(
            'workspaceName' => $this->mockWorkspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => array()
        );
        $this->mockContextFactory->expects($this->any())->method('create')->with($expectedContextProperties)->will($this->returnValue($mockContext));

        $mockNodeData1 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));
        $mockNodeData2->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));

        $mockNode1 = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNode1->expects($this->any())->method('getNodeData')->will($this->returnValue($mockNodeData1));
        $mockNode1->expects($this->any())->method('getPath')->will($this->returnValue('/node1'));

        $this->mockNodeFactory->expects($this->at(0))->method('createFromNodeData')->with($mockNodeData1, $mockContext)->will($this->returnValue($mockNode1));
        $this->mockNodeFactory->expects($this->at(1))->method('createFromNodeData')->with($mockNodeData2, $mockContext)->will($this->returnValue(null));

        $this->mockNodeDataRepository->expects($this->atLeastOnce())->method('findByWorkspace')->with($this->mockWorkspace)->will($this->returnValue(array($mockNodeData1, $mockNodeData2)));

        $actualResult = $this->publishingService->getUnpublishedNodes($this->mockWorkspace);
        $this->assertSame($actualResult, array($mockNode1));
    }

    /**
     * @test
     */
    public function getUnpublishedNodesCountReturnsTheNumberOfNodesInTheGivenWorkspaceMinusItsRootNode()
    {
        $this->mockWorkspace->expects($this->atLeastOnce())->method('getNodeCount')->will($this->returnValue(123));
        $actualResult = $this->publishingService->getUnpublishedNodesCount($this->mockWorkspace);
        $expectedResult = 122;
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheGivenNodeFromItsWorkspaceToTheSpecifiedTargetWorkspace()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode), $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheGivenNodeToItsBaseWorkspaceIfNoTargetWorkspaceIsSpecified()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode), $this->mockBaseWorkspace);
        $this->publishingService->publishNode($mockNode);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeIsADocument()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects($this->atLeastOnce())->method('isOfType')->with('Neos.Neos:Document')->will($this->returnValue(true));
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));
        $mockNode->expects($this->atLeastOnce())->method('getChildNodes')->with('Neos.Neos:ContentCollection')->will($this->returnValue(array($mockChildNode)));

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode, $mockChildNode), $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }


    /**
     * @test
     */
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeTypeHasChildNodes()
    {
        $mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $mockChildNode = $this->getMockBuilder(NodeInterface::class)->getMock();

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->setMethods(array('hasConfiguration', 'isOfType'))->getMock();
        $mockNodeType->expects($this->atLeastOnce())->method('hasConfiguration')->with('childNodes')->will($this->returnValue(true));
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));
        $mockNode->expects($this->atLeastOnce())->method('getChildNodes')->with('Neos.Neos:ContentCollection')->will($this->returnValue(array($mockChildNode)));

        $mockTargetWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode, $mockChildNode), $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }
}
