<?php
namespace TYPO3\Neos\Tests\Unit\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Test case for the Workspace PublishingService
 *
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
     * @var DomainRepository
     */
    protected $mockDomainRepository;

    /**
     * @var SiteRepository
     */
    protected $mockSiteRepository;

    /**
     * @var Workspace
     */
    protected $mockWorkspace;

    /**
     * @var \TYPO3\Flow\Persistence\QueryResultInterface
     */
    protected $mockQueryResult;

    /**
     * @var \TYPO3\Neos\Domain\Model\Site
     */
    protected $mockSite;

    /**
     * @var ContentDimensionPresetSourceInterface
     */
    protected $mockContentDimensionPresetSource;

    public function setUp()
    {
        $this->publishingService = new PublishingService();

        $this->mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->disableOriginalConstructor()->setMethods(array('findOneByName'))->getMock();
        $this->inject($this->publishingService, 'workspaceRepository', $this->mockWorkspaceRepository);

        $this->mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findByWorkspace'))->getMock();
        $this->inject($this->publishingService, 'nodeDataRepository', $this->mockNodeDataRepository);

        $this->mockNodeFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'nodeFactory', $this->mockNodeFactory);

        $this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'contextFactory', $this->mockContextFactory);

        $this->mockDomainRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\DomainRepository')->disableOriginalConstructor()->getMock();
        $this->inject($this->publishingService, 'domainRepository', $this->mockDomainRepository);

        $this->mockSiteRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\SiteRepository')->disableOriginalConstructor()->getMock();
        $this->mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
        $this->mockSiteRepository->expects($this->any())->method('findFirstOnline')->will($this->returnValue($this->mockSite));
        $this->inject($this->publishingService, 'siteRepository', $this->mockSiteRepository);

        $this->mockContentDimensionPresetSource = $this->getMockBuilder(ContentDimensionPresetSourceInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockContentDimensionPresetSource->expects($this->any())->method('findPresetsByTargetValues')->will($this->returnArgument(0));
        $this->inject($this->publishingService, 'contentDimensionPresetSource', $this->mockContentDimensionPresetSource);

        $this->mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $this->mockWorkspace->expects($this->any())->method('getName')->with()->will($this->returnValue('workspace-name'));
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
        $mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();

        $expectedContextProperties = array(
            'workspaceName' => $this->mockWorkspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'currentSite' => $this->mockSite,
            'dimensions' => array()
        );
        $this->mockContextFactory->expects($this->any())->method('create')->with($expectedContextProperties)->will($this->returnValue($mockContext));

        $mockNodeData1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));
        $mockNodeData2->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));

        $mockNode1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
        $mockNode2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

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
        $mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();

        $expectedContextProperties = array(
            'workspaceName' => $this->mockWorkspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'currentSite' => $this->mockSite,
            'dimensions' => array()
        );
        $this->mockContextFactory->expects($this->any())->method('create')->with($expectedContextProperties)->will($this->returnValue($mockContext));

        $mockNodeData1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
        $mockNodeData2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();

        $mockNodeData1->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));
        $mockNodeData2->expects($this->any())->method('getDimensionValues')->will($this->returnValue(array()));

        $mockNode1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

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
        $mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

        $mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $mockTargetWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode), $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheGivenNodeToLiveWorkspaceIfNoTargetWorkspaceIsSpecified()
    {
        $mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

        $mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode), $mockLiveWorkspace);
        $this->publishingService->publishNode($mockNode);
    }

    /**
     * @test
     */
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeIsADocument()
    {
        $mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
        $mockChildNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

        $mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
        $mockNodeType->expects($this->atLeastOnce())->method('isOfType')->with('TYPO3.Neos:Document')->will($this->returnValue(true));
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));
        $mockNode->expects($this->atLeastOnce())->method('getChildNodes')->with('TYPO3.Neos:ContentCollection')->will($this->returnValue(array($mockChildNode)));

        $mockTargetWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode, $mockChildNode), $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }


    /**
     * @test
     */
    public function publishNodePublishesTheNodeAndItsChildNodeCollectionsIfTheNodeTypeHasChildNodes()
    {
        $mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
        $mockChildNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

        $mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->setMethods(array('hasConfiguration', 'isOfType'))->getMock();
        $mockNodeType->expects($this->atLeastOnce())->method('hasConfiguration')->with('childNodes')->will($this->returnValue(true));
        $mockNode->expects($this->atLeastOnce())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockNode->expects($this->atLeastOnce())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));
        $mockNode->expects($this->atLeastOnce())->method('getChildNodes')->with('TYPO3.Neos:ContentCollection')->will($this->returnValue(array($mockChildNode)));

        $mockTargetWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $this->mockWorkspace->expects($this->atLeastOnce())->method('publishNodes')->with(array($mockNode, $mockChildNode), $mockTargetWorkspace);
        $this->publishingService->publishNode($mockNode, $mockTargetWorkspace);
    }
}
