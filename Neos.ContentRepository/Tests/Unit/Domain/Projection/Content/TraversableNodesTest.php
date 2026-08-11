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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Data providers have to be static, so they cannot build mocks. Instead they refer to
     * nodes by index and this helper resolves those indices to the mocks built in setUp().
     *
     * @param int[] $nodeIndices
     * @return TraversableNodeInterface[]
     */
    private function resolveNodes(array $nodeIndices): array
    {
        return array_map(fn (int $nodeIndex) => $this->resolveNode($nodeIndex), $nodeIndices);
    }

    private function resolveNode(int $nodeIndex): TraversableNodeInterface
    {
        return [$this->mockNode1, $this->mockNode2, $this->mockNode3][$nodeIndex];
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

    #[Test]
    public function fromArrayThrowsAnExceptionIfGetsPassedAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        TraversableNodes::fromArray(['foo']);
    }

    #[Test]
    public function fromArrayThrowsAnExceptionIfGetsPassedAnInvalidObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        TraversableNodes::fromArray([new \stdClass()]);
    }

    public static function mergeDataProvider(): array
    {
        return [
            ['nodes1' => [], 'nodes2' => [], 'expectedResult' => []],
            ['nodes1' => [0], 'nodes2' => [1], 'expectedResult' => [0, 1]],
            ['nodes1' => [0, 1], 'nodes2' => [2], 'expectedResult' => [0, 1, 2]],
            ['nodes1' => [0], 'nodes2' => [1, 2], 'expectedResult' => [0, 1, 2]],

            // TODO is the following expected or should TraversableNodes deduplicate nodes?
            ['nodes1' => [0], 'nodes2' => [0], 'expectedResult' => [0, 0]],
        ];
    }

    /**
     * @param int[] $nodes1
     * @param int[] $nodes2
     * @param int[] $expectedResult
     */
    #[DataProvider('mergeDataProvider')]
    #[Test]
    public function mergeTests(array $nodes1, array $nodes2, array $expectedResult)
    {
        $nodes1 = TraversableNodes::fromArray($this->resolveNodes($nodes1));
        $nodes2 = TraversableNodes::fromArray($this->resolveNodes($nodes2));
        $mergeResult = $nodes1->merge($nodes2);
        self::assertSame($this->resolveNodes($expectedResult), $mergeResult->toArray());
    }

    #[Test]
    public function isEmptyIsTrueIfTraversableNodesDoesNotContainAnyNodes()
    {
        $nodes = TraversableNodes::fromArray([]);
        self::assertTrue($nodes->isEmpty());
    }

    #[Test]
    public function isEmptyIsFalseIfTraversableNodesContainNodes()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1]);
        self::assertFalse($nodes->isEmpty());
    }

    #[Test]
    public function countReturnsZeroIfTraversableNodesIsEmpty()
    {
        $nodes = TraversableNodes::fromArray([]);
        self::assertSame(0, $nodes->count());
    }

    #[Test]
    public function countReturnsNumberOfNodes()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        self::assertSame(2, $nodes->count());
    }

    #[Test]
    public function previousThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->previous($this->mockNode3);
    }

    #[Test]
    public function previousThrowsExceptionIfReferenceNodeIsTheFirstNodeInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542902422);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $nodes->previous($this->mockNode1);
    }

    #[Test]
    public function previousReturnsThePreviousNode()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $result = $nodes->previous($this->mockNode2);

        self::assertSame($this->mockNode1, $result);
    }

    #[Test]
    public function previousAllThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->previousAll($this->mockNode3);
    }

    public static function previousAllDataProvider(): array
    {
        return [
            ['nodes' => [0, 1, 2], 'reference' => 0, 'expectedResult' => []],
            ['nodes' => [0, 1, 2], 'reference' => 1, 'expectedResult' => [0]],
            ['nodes' => [0, 1, 2], 'reference' => 2, 'expectedResult' => [0, 1]],
            ['nodes' => [0], 'reference' => 0, 'expectedResult' => []],
        ];
    }

    /**
     * @param int[] $nodes
     * @param int[] $expectedResult
     */
    #[DataProvider('previousAllDataProvider')]
    #[Test]
    public function previousAllTests(array $nodes, int $reference, array $expectedResult)
    {
        $traversableNodes = TraversableNodes::fromArray($this->resolveNodes($nodes));
        $result = $traversableNodes->previousAll($this->resolveNode($reference));

        self::assertSame($this->resolveNodes($expectedResult), $result->toArray());
    }

    #[Test]
    public function nextThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->next($this->mockNode3);
    }

    #[Test]
    public function nextThrowsExceptionIfReferenceNodeIsTheLastNodeInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542902858);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $nodes->next($this->mockNode3);
    }

    #[Test]
    public function nextReturnsTheNextNode()
    {
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2, $this->mockNode3]);
        $result = $nodes->next($this->mockNode2);

        self::assertSame($this->mockNode3, $result);
    }

    #[Test]
    public function nextAllThrowsExceptionIfReferenceNodeIsNotFoundInTheSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1542901216);
        $nodes = TraversableNodes::fromArray([$this->mockNode1, $this->mockNode2]);
        $nodes->nextAll($this->mockNode3);
    }

    public static function nextAllDataProvider(): array
    {
        return [
            ['nodes' => [0, 1, 2], 'reference' => 2, 'expectedResult' => []],
            ['nodes' => [0, 1, 2], 'reference' => 0, 'expectedResult' => [1, 2]],
            ['nodes' => [0, 1, 2], 'reference' => 1, 'expectedResult' => [2]],
            ['nodes' => [0], 'reference' => 0, 'expectedResult' => []],
        ];
    }

    /**
     * @param int[] $nodes
     * @param int[] $expectedResult
     */
    #[DataProvider('nextAllDataProvider')]
    #[Test]
    public function nextAllTests(array $nodes, int $reference, array $expectedResult)
    {
        $traversableNodes = TraversableNodes::fromArray($this->resolveNodes($nodes));
        $result = $traversableNodes->nextAll($this->resolveNode($reference));

        self::assertSame($this->resolveNodes($expectedResult), $result->toArray());
    }
}
