<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Unit\Domain\Projection;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeSortPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NodeSortPathTest extends TestCase
{
    public static function providerWithReplacedNodeSortKey(): array
    {
        return [
            ['a0', 'b5', 'b5'],
            ['a0/a5', 'b5', 'a0/b5'],
            ['a0/a5/a4', 'b5', 'a0/a5/b5'],
        ];
    }

    #[DataProvider('providerWithReplacedNodeSortKey')]
    #[Test]
    public function withReplacedNodeSortKey(string $path, string $nodeSortKey, string $expected): void
    {
        $nodeSortPath = NodeSortPath::fromString($path);
        $newNodeSortPath = $nodeSortPath->withReplacedNodeSortKey($nodeSortKey);
        $this->assertEquals($expected, $newNodeSortPath->value);
    }

    public static function providerWithAddedNodeSortKeySegment(): array
    {
        return [
            ['a0', 'b5', 'a0/b5'],
            ['a0/a5', 'b5', 'a0/a5/b5'],
            ['a0/a5/a4', 'b5', 'a0/a5/a4/b5'],
        ];
    }

    #[DataProvider('providerWithAddedNodeSortKeySegment')]
    #[Test]
    public function withAddedNodeSortKeySegment(string $path, string $nodeSortKey, string $expected): void
    {
        $nodeSortPath = NodeSortPath::fromString($path);
        $newNodeSortPath = $nodeSortPath->withAddedNodeSortKeySegment($nodeSortKey);
        $this->assertEquals($expected, $newNodeSortPath->value);
    }

    public static function providerGetNodeSortKey(): array
    {
        return [
            ['a0', 'a0'],
            ['a0/b5', 'b5'],
            ['a0/a5/b5', 'b5',],
            ['a0/a5/a4/b5', 'b5'],
            ['a0/a5ZZZa4a4a4a4a4a4a4a4a4a4a4a4a4a4a4a4/a4/b5', 'b5'],
        ];
    }

    #[DataProvider('providerGetNodeSortKey')]
    #[Test]
    public function getNodeSortKey(string $path, string $expected): void
    {
        $nodeSortPath = NodeSortPath::fromString($path);
        $this->assertEquals($expected, $nodeSortPath->getNodeSortKey());
    }

    #[Test]
    public function testIsRoot(): void
    {
        $nodeSortPath = NodeSortPath::fromString('a0');
        $this->assertTrue($nodeSortPath->isRoot());

        $nodeSortPath = NodeSortPath::fromString('a0/a0');
        $this->assertFalse($nodeSortPath->isRoot());
    }

    #[Test]
    public function testNonEmptyFragments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NodeSortPath::fromString('');
    }

    #[Test]
    public function nodeSortKeyExceedsMaxKeyLength(): void
    {
        $this->assertFalse(
            NodeSortPath::fromString('012345678901234567890123456789012345')->nodeSortKeyExceedsMaxKeyLength()
        );
        $this->assertTrue(
            NodeSortPath::fromString('0123456789012345678901234567890123456')->nodeSortKeyExceedsMaxKeyLength()
        );

        $this->assertFalse(
            NodeSortPath::fromString('a0/a0/a0/a0/a0/a0/012345678901234567890123456789012345')->nodeSortKeyExceedsMaxKeyLength()
        );
        $this->assertTrue(
            NodeSortPath::fromString('a0/a0/a0/a0/a0/a0/0123456789012345678901234567890123456')->nodeSortKeyExceedsMaxKeyLength()
        );
    }
}
