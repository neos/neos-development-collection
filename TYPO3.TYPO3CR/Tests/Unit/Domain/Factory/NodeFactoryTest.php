<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Factory;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Testcase for the NodeFactory
 *
 */
class NodeFactoryTest extends UnitTestCase
{
    /**
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManagerMock;

    /**
     * @var ReflectionService
     */
    protected $reflectionServiceMock;


    /**
     * Prepare test objects
     */
    protected function setUp()
    {
        $this->nodeFactory = $this->getMockBuilder(NodeFactory::class)->setMethods(array('filterNodeByContext'))->getMock();

        $this->nodeFactory->expects(self::any())->method('filterNodeByContext')->willReturnArgument(0);

        $this->reflectionServiceMock = $this->createMock(ReflectionService::class);
        $this->reflectionServiceMock->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(NodeInterface::class)->willReturn(array(Node::class));

        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->objectManagerMock->expects(self::any())->method('get')->with(ReflectionService::class)->willReturn($this->reflectionServiceMock);
        $this->objectManagerMock->expects(self::any())->method('getClassNameByObjectName')->with(NodeInterface::class)->willReturn(Node::class);

        $this->inject($this->nodeFactory, 'objectManager', $this->objectManagerMock);
    }

    /**
     * @test
     */
    public function createFromNodeDataCreatesANodeWithTheGivenContextAndNodeData()
    {
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData->expects(self::any())->method('getIdentifier')->willReturn('0068371a-c108-99cb-3aa5-81b8852a2d12');
        $mockNodeData->expects(self::any())->method('getNodeType')->willReturn($mockNodeType);

        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $node = $this->nodeFactory->createFromNodeData($mockNodeData, $mockContext);

        self::assertEquals($mockContext, $node->getContext());
        self::assertEquals($mockNodeData, $node->getNodeData());
        self::assertEquals('0068371a-c108-99cb-3aa5-81b8852a2d12', $node->getIdentifier());
    }

    /**
     * @test
     */
    public function createContextMatchingNodeDataCreatesMatchingContext()
    {
        $dimensionValues = array('language' => array('is'));
        $workspaceName = 'some-workspace';

        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $mockWorkspace = $this->getMockBuilder(Workspace::class)->setMockClassName('MockWorkspace')->disableOriginalConstructor()->getMock();
        $mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue($workspaceName));

        $mockContextFactory = $this->createMock(ContextFactoryInterface::class);
        $mockContextFactory->expects(self::once())->method('create')->with(array(
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => $dimensionValues
        ))->willReturn($mockContext);

        $this->inject($this->nodeFactory, 'contextFactory', $mockContextFactory);

        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData->expects(self::any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
        $mockNodeData->expects(self::any())->method('getDimensionValues')->willReturn($dimensionValues);

        $context = $this->nodeFactory->createContextMatchingNodeData($mockNodeData);

        self::assertEquals($mockContext, $context);
    }
}
