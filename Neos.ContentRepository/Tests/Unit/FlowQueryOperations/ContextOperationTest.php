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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Eel\FlowQueryOperations\ContextOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery ContextOperation
 */
class ContextOperationTest extends AbstractQueryOperationsTest
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

    /**
     * @test
     */
    public function canEvaluateReturnsTrueIfNodeIsInContext()
    {
        $mockNode = $this->createMock(NodeInterface::class);

        $result = $this->operation->canEvaluate([$mockNode]);
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateCreatesModifiedContextFromFactoryUsingMergedProperties()
    {
        $suppliedContextProperties = ['infiniteImprobabilityDrive' => true];
        $nodeContextProperties = ['infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true];
        $expectedModifiedContextProperties = ['infiniteImprobabilityDrive' => true, 'autoRemoveUnsuitableContent' => true];

        $mockNode = $this->createMock(NodeInterface::class);
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockContextFactory->expects(self::atLeastOnce())->method('create')->with($expectedModifiedContextProperties)->will(self::returnValue($modifiedNodeContext));

        $this->operation->evaluate($mockFlowQuery, [$suppliedContextProperties]);
    }

    /**
     * @test
     */
    public function evaluateGetsAndSetsNodesInContextFromModifiedContextByIdentifier()
    {
        $suppliedContextProperties = ['infiniteImprobabilityDrive' => true];
        $nodeContextProperties = ['infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true];
        $nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects(self::any())->method('getIdentifier')->will(self::returnValue($nodeIdentifier));
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $nodeInModifiedContext = $this->createMock(NodeInterface::class);
        $nodeInModifiedContext->expects(self::any())->method('getPath')->will(self::returnValue('/foo/bar'));
        $this->mockContextFactory->expects(self::any())->method('create')->will(self::returnValue($modifiedNodeContext));

        $modifiedNodeContext->expects(self::once())->method('getNodeByIdentifier')->with($nodeIdentifier)->will(self::returnValue($nodeInModifiedContext));
        $mockFlowQuery->expects(self::atLeastOnce())->method('setContext')->with([$nodeInModifiedContext]);

        $this->operation->evaluate($mockFlowQuery, [$suppliedContextProperties]);
    }

    /**
     * @test
     */
    public function evaluateSkipsNodesNotAvailableInModifiedContext()
    {
        $suppliedContextProperties = ['infiniteImprobabilityDrive' => true];
        $nodeContextProperties = ['infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true];
        $nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects(self::any())->method('getIdentifier')->will(self::returnValue($nodeIdentifier));
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockContextFactory->expects(self::any())->method('create')->will(self::returnValue($modifiedNodeContext));

        $modifiedNodeContext->expects(self::once())->method('getNodeByIdentifier')->with($nodeIdentifier)->will(self::returnValue(null));
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
        $mockNodeContext->expects(self::any())->method('getProperties')->will(self::returnValue($nodeContextProperties));

        $mockNode->expects(self::any())->method('getContext')->will(self::returnValue($mockNodeContext));

        $mockFlowQuery = $this->getMockBuilder(FlowQuery::class)->disableOriginalConstructor()->getMock();
        $mockFlowQuery->expects(self::any())->method('getContext')->will(self::returnValue([$mockNode]));
        return $mockFlowQuery;
    }
}
