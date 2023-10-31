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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Abstract base class for the Query Operation tests
 */
abstract class AbstractQueryOperationsTest extends UnitTestCase
{
    protected function mockNode(string $nodeAggregateId): Node
    {
        /** @var Node|MockObject $mockNode */
        $mockNode = $this->getMockBuilder(Node::class)->getMock();
        $mockNode->method('getNodeAggregateId')->willReturn(NodeAggregateId::fromString($nodeAggregateId));
        $mockNode->method('equals')->willReturnCallback(function (Node $other) use ($mockNode) {
            return $other === $mockNode;
        });
        return $mockNode;
    }
}
