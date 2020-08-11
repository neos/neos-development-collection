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

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Abstract base class for the Query Operation tests
 */
abstract class AbstractQueryOperationsTest extends UnitTestCase
{
    protected function mockNode(string $nodeAggregateIdentifier): TraversableNodeInterface
    {
        /** @var TraversableNodeInterface|MockObject $mockNode */
        $mockNode = $this->getMockBuilder(TraversableNodeInterface::class)->getMock();
        $mockNode->method('getNodeAggregateIdentifier')->willReturn(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
        $mockNode->method('equals')->willReturnCallback(function (TraversableNodeInterface $other) use ($mockNode) {
            return $other === $mockNode;
        });
        return $mockNode;
    }
}
