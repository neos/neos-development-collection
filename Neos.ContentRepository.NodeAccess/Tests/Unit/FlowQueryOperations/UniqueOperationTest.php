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

use Neos\ContentRepository\NodeAccess\FlowQueryOperations\UniqueOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery UniqueOperation
 */
class UniqueOperationTest extends AbstractQueryOperationsTest
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
        $operation = new UniqueOperation();
        self::assertTrue($operation->canEvaluate([]));
        self::assertTrue($operation->canEvaluate([$node]));
        self::assertFalse($operation->canEvaluate([123]));
        self::assertFalse($operation->canEvaluate(["string"]));
        self::assertFalse($operation->canEvaluate([new \stdClass()]));
    }

    /**
     * @test
     */
    public function willRemoveDuplicateEntriesWithTheSameNodeAggregateId()
    {
        $node1 = $this->mockNode('nudelsuppe');
        $node2 = $this->mockNode('tomatensuppe');
        $node3 = $this->mockNode('nudelsuppe');
        $node4 = $this->mockNode('bohnensuppe');

        $flowQuery = new FlowQuery([$node1, $node2, $node3, $node4, $node1, $node2]);

        $operation = new UniqueOperation();
        $operation->evaluate($flowQuery, []);

        $output = $flowQuery->getContext();
        self::assertSame([$node1, $node2, $node4], $output);
    }
}
