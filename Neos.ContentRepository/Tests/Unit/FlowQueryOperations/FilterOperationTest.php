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

use Neos\ContentRepository\Eel\FlowQueryOperations\FilterOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the FlowQuery FilterOperation
 */
class FilterOperationTest extends AbstractQueryOperationsTest
{

    /**
     * @test
     */
    public function filterWithIdentifierUsesNodeAggregateIdentifier()
    {
        $node1 = $this->mockNode('node1-identifier-uuid');
        $node2 = $this->mockNode('node2-identifier-uuid');

        $context = [$node1, $node2];
        $q = new FlowQuery($context);

        $operation = new FilterOperation();
        $operation->evaluate($q, ['#node2-identifier-uuid']);

        self::assertEquals([$node2], $q->getContext());
    }

    /**
     * @test
     */
    public function filterWithNodeInstanceIsSupported()
    {
        $node1 = $this->mockNode('node1');
        $node2 = $this->mockNode('node2');

        $context = [$node1, $node2];
        $q = new FlowQuery($context);

        $operation = new FilterOperation();
        $operation->evaluate($q, [$node2]);

        self::assertEquals([$node2], $q->getContext());
    }
}
