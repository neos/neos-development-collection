<?php
namespace Neos\ContentRepository\NodeAccess\Tests\Unit\FlowQueryOperations;

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
use Neos\ContentRepository\NodeAccess\FlowQueryOperations\RemoveOperation;

/**
 * Testcase for the FlowQuery RemoveOperation
 */
class RemoveOperationTest extends AbstractQueryOperationsTest
{

    public function setUp(): void
    {
        $this->markTestSkipped('fix and re-enable for Neos 9.0');
    }

    /**
     * @test
     */
    public function canBeAppliedOnceNodesAreInContextOrContextIsEmpty()
    {
        $node = $this->mockNode('nudelsuppe');
        $operation = new RemoveOperation();
        self::assertTrue($operation->canEvaluate([]));
        self::assertTrue($operation->canEvaluate([$node]));
        self::assertFalse($operation->canEvaluate([123]));
        self::assertFalse($operation->canEvaluate(["string"]));
        self::assertFalse($operation->canEvaluate([new \stdClass()]));
    }

    /**
     * @test
     */
    public function removeWillRemoveTheNodeGivenAsSingleArgument()
    {
        $node1 = $this->mockNode('nudelsuppe');
        $node2 = $this->mockNode('tomatensuppe');

        $nodeToRemove = $this->mockNode('tomatensuppe');

        $flowQuery = new FlowQuery([$node1, $node2]);

        $operation = new RemoveOperation();
        $operation->evaluate($flowQuery, [$nodeToRemove]);

        $output = $flowQuery->getContext();
        self::assertContains($node1, $output);
        self::assertNotContains($node2, $output);
    }

    /**
     * @test
     */
    public function removeWillRemoveTheNodeGivenAsArrayArgument()
    {
        $node1 = $this->mockNode('nudelsuppe');
        $node2 = $this->mockNode('tomatensuppe');

        $nodeToRemove = $this->mockNode('tomatensuppe');

        $q = new FlowQuery([$node1, $node2]);

        $operation = new RemoveOperation();
        $operation->evaluate($q, [[$nodeToRemove]]);

        $output = $q->getContext();
        self::assertContains($node1, $output);
        self::assertNotContains($node2, $output);
    }

    /**
     * @test
     */
    public function removeWillRemoveTheNodeGivenAsFlowQueryArgument()
    {
        $node1 = $this->mockNode('nudelsuppe');
        $node2 = $this->mockNode('tomatensuppe');

        $flowQueryToRemove = new FlowQuery($this->mockNode('tomatensuppe'));

        $q = new FlowQuery([$node1, $node2]);

        $operation = new RemoveOperation();
        $operation->evaluate($q, [$flowQueryToRemove]);

        $output = $q->getContext();
        self::assertContains($node1, $output);
        self::assertNotContains($node2, $output);
    }
}
