<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Factory;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

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
    public function setUp(): void
    {
        $this->nodeFactory = $this->getMockBuilder(NodeFactory::class)->setMethods(['filterNodeByContext'])->getMock();

        $this->nodeFactory->expects(self::any())->method('filterNodeByContext')->willReturnArgument(0);

        $this->reflectionServiceMock = $this->createMock(ReflectionService::class);
        $this->reflectionServiceMock->expects(self::any())->method('getAllImplementationClassNamesForInterface')->with(NodeInterface::class)->willReturn([Node::class]);

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
        $dimensionValues = ['language' => ['is']];
        $workspaceName = 'some-workspace';

        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $mockWorkspace = $this->getMockBuilder(Workspace::class)->setMockClassName('MockWorkspace')->disableOriginalConstructor()->getMock();
        $mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue($workspaceName));

        $mockContextFactory = $this->createMock(ContextFactoryInterface::class);
        $mockContextFactory->expects(self::once())->method('create')->with([
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => $dimensionValues
        ])->willReturn($mockContext);

        $this->inject($this->nodeFactory, 'contextFactory', $mockContextFactory);

        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData->expects(self::any())->method('getWorkspace')->will(self::returnValue($mockWorkspace));
        $mockNodeData->expects(self::any())->method('getDimensionValues')->willReturn($dimensionValues);

        $context = $this->nodeFactory->createContextMatchingNodeData($mockNodeData);

        self::assertEquals($mockContext, $context);
    }
}
