<?php
namespace Neos\ContentRepository\Tests\Unit\FlowQueryOperations;

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
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Eel\FlowQueryOperations\ContextOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery ContextOperation
 */
class ContextOperationTest extends AbstractQueryOperationsTestCase
{
    /**
     * @var ContextOperation
     */
    protected $operation;

    /**
     * @var ContextFactoryInterface
     */
    protected $mockContextFactory;

    public function setUp(): void
    {
        $this->operation = new ContextOperation();
        $this->mockContextFactory = $this->createMock(ContextFactoryInterface::class);
        $this->inject($this->operation, 'contextFactory', $this->mockContextFactory);
    }

    #[Test]
    public function canEvaluateReturnsTrueIfNodeIsInContext()
    {
        $mockNode = $this->createMock(NodeInterface::class);

        $result = $this->operation->canEvaluate([$mockNode]);
        self::assertTrue($result);
    }

    #[Test]
    public function evaluateCreatesModifiedContextFromFactoryUsingMergedProperties()
    {
        $suppliedContextProperties = ['infiniteImprobabilityDrive' => true];
        $nodeContextProperties = ['infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true];
        $expectedModifiedContextProperties = ['infiniteImprobabilityDrive' => true, 'autoRemoveUnsuitableContent' => true];

        $mockNode = $this->createMock(NodeInterface::class);
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockContextFactory->expects(self::atLeastOnce())->method('create')->with($expectedModifiedContextProperties)->willReturn($modifiedNodeContext);

        $this->operation->evaluate($mockFlowQuery, [$suppliedContextProperties]);
    }

    #[Test]
    public function evaluateGetsAndSetsNodesInContextFromModifiedContextByIdentifier()
    {
        $suppliedContextProperties = ['infiniteImprobabilityDrive' => true];
        $nodeContextProperties = ['infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true];
        $nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects(self::any())->method('getIdentifier')->willReturn($nodeIdentifier);
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $nodeInModifiedContext = $this->createMock(NodeInterface::class);
        $nodeInModifiedContext->expects(self::any())->method('getPath')->willReturn('/foo/bar');
        $this->mockContextFactory->expects(self::any())->method('create')->willReturn($modifiedNodeContext);

        $modifiedNodeContext->expects(self::once())->method('getNodeByIdentifier')->with($nodeIdentifier)->willReturn($nodeInModifiedContext);
        $mockFlowQuery->expects(self::atLeastOnce())->method('setContext')->with([$nodeInModifiedContext]);

        $this->operation->evaluate($mockFlowQuery, [$suppliedContextProperties]);
    }

    #[Test]
    public function evaluateSkipsNodesNotAvailableInModifiedContext()
    {
        $suppliedContextProperties = ['infiniteImprobabilityDrive' => true];
        $nodeContextProperties = ['infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true];
        $nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects(self::any())->method('getIdentifier')->willReturn($nodeIdentifier);
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockContextFactory->expects(self::any())->method('create')->willReturn($modifiedNodeContext);

        $modifiedNodeContext->expects(self::once())->method('getNodeByIdentifier')->with($nodeIdentifier)->willReturn(null);
        $mockFlowQuery->expects(self::atLeastOnce())->method('setContext')->with([]);

        $this->operation->evaluate($mockFlowQuery, [$suppliedContextProperties]);
    }

    /**
     * @param NodeInterface $mockNode
     * @param array $nodeContextProperties
     * @return FlowQuery
     */
    protected function buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties)
    {
        $mockNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockNodeContext->expects(self::any())->method('getProperties')->willReturn($nodeContextProperties);

        $mockNode->expects(self::any())->method('getContext')->willReturn($mockNodeContext);

        $mockFlowQuery = $this->getMockBuilder(FlowQuery::class)->disableOriginalConstructor()->getMock();
        $mockFlowQuery->expects(self::any())->method('getContext')->willReturn([$mockNode]);
        return $mockFlowQuery;
    }
}
