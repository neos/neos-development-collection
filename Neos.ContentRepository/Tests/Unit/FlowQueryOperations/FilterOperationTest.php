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

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Eel\FlowQueryOperations\FilterOperation;

/**
 * Testcase for the FlowQuery FilterOperation
 */
class FilterOperationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function filterWithIdentifierUsesNodeAggregateIdentifier()
    {
        $node1 = $this->createMock(TraversableNodeInterface::class);
        $node1->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(new NodeAggregateIdentifier('node1-identifier-uuid')));
        $node2 = $this->createMock(TraversableNodeInterface::class);
        $node2->expects($this->any())->method('getNodeAggregateIdentifier')->will($this->returnValue(new NodeAggregateIdentifier('node2-identifier-uuid')));

        $context = [$node1, $node2];
        $q = new FlowQuery($context);

        $operation = new FilterOperation();
        $operation->evaluate($q, ['#node2-identifier-uuid']);

        $this->assertEquals([$node2], $q->getContext());
    }

    /**
     * @test
     */
    public function filterWithNodeInstanceIsSupported()
    {
        $node1 = $this->createMock(TraversableNodeInterface::class);
        $node2 = $this->createMock(TraversableNodeInterface::class);

        $context = [$node1, $node2];
        $q = new FlowQuery($context);

        $operation = new FilterOperation();
        $operation->evaluate($q, [$node2]);

        $this->assertEquals([$node2], $q->getContext());
    }
}
