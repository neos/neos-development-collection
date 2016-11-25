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

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Eel\FlowQueryOperations\ContextOperation;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Testcase for the FlowQuery ContextOperation
 */
class ContextOperationTest extends UnitTestCase
{
    /**
     * @var ContextOperation
     */
    protected $operation;

    /**
     * @var ContextFactoryInterface
     */
    protected $mockContextFactory;

    public function setUp()
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

        $result = $this->operation->canEvaluate(array($mockNode));
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function evaluateCreatesModifiedContextFromFactoryUsingMergedProperties()
    {
        $suppliedContextProperties = array('infiniteImprobabilityDrive' => true);
        $nodeContextProperties = array('infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true);
        $expectedModifiedContextProperties = array('infiniteImprobabilityDrive' => true, 'autoRemoveUnsuitableContent' => true);

        $mockNode = $this->createMock(NodeInterface::class);
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockContextFactory->expects($this->atLeastOnce())->method('create')->with($expectedModifiedContextProperties)->will($this->returnValue($modifiedNodeContext));

        $this->operation->evaluate($mockFlowQuery, array($suppliedContextProperties));
    }

    /**
     * @test
     */
    public function evaluateGetsAndSetsNodesInContextFromModifiedContextByIdentifier()
    {
        $suppliedContextProperties = array('infiniteImprobabilityDrive' => true);
        $nodeContextProperties = array('infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true);
        $nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $nodeInModifiedContext = $this->createMock(NodeInterface::class);
        $nodeInModifiedContext->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
        $this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($modifiedNodeContext));

        $modifiedNodeContext->expects($this->once())->method('getNodeByIdentifier')->with($nodeIdentifier)->will($this->returnValue($nodeInModifiedContext));
        $mockFlowQuery->expects($this->atLeastOnce())->method('setContext')->with(array($nodeInModifiedContext));

        $this->operation->evaluate($mockFlowQuery, array($suppliedContextProperties));
    }

    /**
     * @test
     */
    public function evaluateSkipsNodesNotAvailableInModifiedContext()
    {
        $suppliedContextProperties = array('infiniteImprobabilityDrive' => true);
        $nodeContextProperties = array('infiniteImprobabilityDrive' => false, 'autoRemoveUnsuitableContent' => true);
        $nodeIdentifier = 'c575c430-c971-11e3-a6e7-14109fd7a2dd';

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($nodeIdentifier));
        $mockFlowQuery = $this->buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties);

        $modifiedNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($modifiedNodeContext));

        $modifiedNodeContext->expects($this->once())->method('getNodeByIdentifier')->with($nodeIdentifier)->will($this->returnValue(null));
        $mockFlowQuery->expects($this->atLeastOnce())->method('setContext')->with(array());

        $this->operation->evaluate($mockFlowQuery, array($suppliedContextProperties));
    }

    /**
     * @param NodeInterface $mockNode
     * @param array $nodeContextProperties
     * @return FlowQuery
     */
    protected function buildFlowQueryWithNodeInContext($mockNode, $nodeContextProperties)
    {
        $mockNodeContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockNodeContext->expects($this->any())->method('getProperties')->will($this->returnValue($nodeContextProperties));

        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockNodeContext));

        $mockFlowQuery = $this->getMockBuilder(FlowQuery::class)->disableOriginalConstructor()->getMock();
        $mockFlowQuery->expects($this->any())->method('getContext')->will($this->returnValue(array($mockNode)));
        return $mockFlowQuery;
    }
}
