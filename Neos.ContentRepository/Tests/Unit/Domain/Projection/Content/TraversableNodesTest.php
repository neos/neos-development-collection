<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Projection\Content;

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
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class TraversableNodesTest extends UnitTestCase
{
    /**
     * @var TraversableNodeInterface|MockObject
     */
    private $mockNode1;

    /**
     * @var TraversableNodeInterface|MockObject
     */
    private $mockNode2;

    /**
     * @var TraversableNodeInterface|MockObject
     */
    private $mockNode3;

    public function setUp(): void
    {
        $this->mockNode1 = $this->mockNode();
        $this->mockNode2 = $this->mockNode();
        $this->mockNode3 = $this->mockNode();
    }

    private function mockNode(): TraversableNodeInterface
    {
        /** @var TraversableNodeInterface|MockObject $mockNode */
        $mockNode = $this->getMockBuilder(TraversableNodeInterface::class)->getMock();
        $mockNode->method('getNodeAggregateIdentifier')->willReturn(NodeAggregateIdentifier::create());
        $mockNode->method('equals')->willReturnCallback(function (TraversableNodeInterface $other) use ($mockNode) {
            return $other === $mockNode;
        });
        return $mockNode;
    }

    /**
     * @test
     */
    public function fromArrayThrowsAnExceptionIfGetsPassedAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        TraversableNodes::fromArray(['foo']);
    }

    /**
     * @test
     */
    public function fromArrayThrowsAnExceptionIfGetsPassedAnInvalidObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        TraversableNodes::fromArray([new \stdClass()]);
    }

    public function mergeDataProvider()
    {
        $mockNode1 = $this->mockNode();
        $mockNode2 = $this->mockNode();
        $mockNode3 = $this->mockNode();
        return [
            ['nodes1' => [], 'nodes2' => [], 'expectedResult' => []],
            ['nodes1' => [$mockNode1], 'nodes2' => [$mockNode2], 'expectedResult' => [$mockNode1, $mockNode2]],
            ['nodes1' => [$mockNode1, $mockNode2], 'nodes2' => [$mockNode3], 'expectedResult' => [$mockNode1, $mockNode2, $mockNode3]],
            ['nodes1' => [$mockNode1], 'nodes2' => [$mockNode2, $mockNode3], 'expectedResult' => [$mockNode1, $mockNode2, $mockNode3]],

            // TODO is the following expected or should TraversableNodes deduplicate nodes?
            ['nodes1' => [$mockNode1], 'nodes2' => [$mockNode1], 'expectedResult' => [$mockNode1, $mockNode1]],
        ];
    }

    /**
     * @param array $nodes1
     * @param array $nodes2
     * @param array $expectedResult
     * @test
     * @dataProvider mergeDataProvider
     */
    public function mergeTests(array $nodes1, array $nodes2, array $expectedResult)
    {
        $nodes1 = TraversableNodes::fromArray($nodes1);
        $nodes2 = TraversableNodes::fromArray($nodes2);
        $mergeResult = $nodes1->merge($nodes2);
        self::assertSame($expectedResult, $mergeResult->toArray());
    }

    /**
     * @test
     */
    public function isEmptyIsTrueIfTraversableNodesDoesNotContainAnyNodes()
    {
        $nodes = TraversableNodes::fromArray([]);
        self::assertTrue($nodes->isEmpty());
    }

    /**
     * @test
     */
    public function isEmptyIsFalseIfTraversableNodesContainNodes()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1]);
        self::assertFalse($nodes->isEmpty());
    }

    /**
     * @test
     */
    public function countReturnsZeroIfTraversableNodesIsEmpty()
    {
        $nodes = TraversableNodes::fromArray([]);
        self::assertSame(0, $nodes->count());
    }

    /**
     * @test
     */
    public function countReturnsNumberOfNodes()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        self::assertSame(2, $nodes->count());
    }

    /**
     * @test
     */
    public function previousThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->previous($this->mockNode3);
    }

    /**
     * @test
     */
    public function previousThrowsExceptionIfReferenceNodeIsTheFirstNodeInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542902422);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $nodes->previous($this->mockNode1);
    }

    /**
     * @test
     */
    public function previousReturnsThePreviousNode()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $result = $nodes->previous($this->mockNode2);

        self::assertSame($this->mockNode1, $result);
    }

    /**
     * @test
     */
    public function previousAllThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->previousAll($this->mockNode3);
    }

    public function previousAllDataProvider()
    {
        $mockNode1 = $this->mockNode();
        $mockNode2 = $this->mockNode();
        $mockNode3 = $this->mockNode();
        return [
            ['nodes' => [$mockNode1, $mockNode2, $mockNode3], 'reference' => $mockNode1, 'expectedResult' => []],
            ['nodes' => [$mockNode1, $mockNode2, $mockNode3], 'reference' => $mockNode2, 'expectedResult' => [$mockNode1]],
            ['nodes' => [$mockNode1, $mockNode2, $mockNode3], 'reference' => $mockNode3, 'expectedResult' => [$mockNode1, $mockNode2]],
            ['nodes' => [$mockNode1], 'reference' => $mockNode1, 'expectedResult' => []],
        ];
    }

    /**
     * @param array $nodes
     * @param TraversableNodeInterface $reference
     * @param array $expectedResult
     * @test
     * @dataProvider previousAllDataProvider
     */
    public function previousAllTests(array $nodes, TraversableNodeInterface $reference, array $expectedResult)
    {
        $traversableNodes = TraversableNodes::fromArray($nodes);
        $result = $traversableNodes->previousAll($reference);

        self::assertSame($expectedResult, $result->toArray());
    }

    /**
     * @test
     */
    public function nextThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->next($this->mockNode3);
    }

    /**
     * @test
     */
    public function nextThrowsExceptionIfReferenceNodeIsTheLastNodeInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542902858);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $nodes->next($this->mockNode3);
    }

    /**
     * @test
     */
    public function nextReturnsTheNextNode()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $result = $nodes->next($this->mockNode2);

        self::assertSame($this->mockNode3, $result);
    }

    /**
     * @test
     */
    public function nextAllThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->nextAll($this->mockNode3);
    }

    public function nextAllDataProvider()
    {
        $mockNode1 = $this->mockNode();
        $mockNode2 = $this->mockNode();
        $mockNode3 = $this->mockNode();
        return [
            ['nodes' => [$mockNode1, $mockNode2, $mockNode3], 'reference' => $mockNode3, 'expectedResult' => []],
            ['nodes' => [$mockNode1, $mockNode2, $mockNode3], 'reference' => $mockNode1, 'expectedResult' => [$mockNode2, $mockNode3]],
            ['nodes' => [$mockNode1, $mockNode2, $mockNode3], 'reference' => $mockNode2, 'expectedResult' => [$mockNode3]],
            ['nodes' => [$mockNode1], 'reference' => $mockNode1, 'expectedResult' => []],
        ];
    }

    /**
     * @param array $nodes
     * @param TraversableNodeInterface $reference
     * @param array $expectedResult
     * @test
     * @dataProvider nextAllDataProvider
     */
    public function nextAllTests(array $nodes, TraversableNodeInterface $reference, array $expectedResult)
    {
        $traversableNodes = TraversableNodes::fromArray($nodes);
        $result = $traversableNodes->nextAll($reference);

        self::assertSame($expectedResult, $result->toArray());
    }
}
