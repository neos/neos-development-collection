<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Domain\Projection;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeSortPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NodeSortPathTest extends TestCase
{
    #[Test]
    public function rootIsEmptyAndChildrenOfRootCarryBareKeys(): void
    {
        $root = NodeSortPath::root();
        self::assertTrue($root->isRoot());
        self::assertSame('', $root->value);
        // root edges have no ingoing relation, so root nodes get a key without a leading separator
        self::assertSame('a0', $root->child('a0')->value);
    }

    #[Test]
    public function childAppendsWithSeparator(): void
    {
        self::assertSame('a0/b3/ZzV', NodeSortPath::fromString('a0/b3')->child('ZzV')->value);
    }

    #[Test]
    public function localKeyIsTheLastSegment(): void
    {
        self::assertSame('ZzV', NodeSortPath::fromString('a0/b3/ZzV')->localKey());
        self::assertSame('a0', NodeSortPath::fromString('a0')->localKey());
    }

    #[Test]
    public function parentStripsTheLastSegment(): void
    {
        self::assertSame('a0/b3', NodeSortPath::fromString('a0/b3/ZzV')->parent()->value);
        self::assertTrue(NodeSortPath::fromString('a0')->parent()->isRoot());
    }

    #[Test]
    public function ancestorPathsAreTheProperPrefixesClosestFirst(): void
    {
        self::assertSame(['a0/b3', 'a0'], NodeSortPath::fromString('a0/b3/ZzV')->ancestorPaths());
        self::assertSame([], NodeSortPath::fromString('a0')->ancestorPaths());
    }

    #[Test]
    public function depthCountsSegments(): void
    {
        self::assertSame(0, NodeSortPath::root()->depth());
        self::assertSame(1, NodeSortPath::fromString('a0')->depth());
        self::assertSame(3, NodeSortPath::fromString('a0/b3/ZzV')->depth());
    }

    /**
     * The descendant range must contain every descendant and nothing else. That works because the separator
     * '/' is 0x2F, directly below '0' (0x30), the lowest base 62 digit.
     */
    #[DataProvider('dataForDescendantRange')]
    #[Test]
    public function descendantRangeIsExact(string $candidate, bool $expectedInRange): void
    {
        $node = NodeSortPath::fromString('a0/b3');
        $inRange = strcmp($candidate, $node->rangeStart()) >= 0 && strcmp($candidate, $node->rangeEnd()) < 0;
        self::assertSame($expectedInRange, $inRange, sprintf('"%s" should%s be in range', $candidate, $expectedInRange ? '' : ' not'));
    }

    public static function dataForDescendantRange(): iterable
    {
        yield 'direct child' => ['a0/b3/a0', true];
        yield 'grandchild' => ['a0/b3/a0/ZzV', true];
        yield 'lowest possible child key' => ['a0/b3/A00000000000000000000000001', true];
        yield 'the node itself' => ['a0/b3', false];
        yield 'preceding sibling' => ['a0/b2', false];
        // 'V' (0x56) sorts above '/' (0x2F), so a sibling with a longer key stays outside the range
        yield 'succeeding sibling with longer key' => ['a0/b3V', false];
        // a legal key: head 'b' denotes a three character integer part, so it lands exactly on the upper bound
        yield 'succeeding sibling keyed exactly at the upper bound' => ['a0/b30', false];
        yield 'descendant of that sibling' => ['a0/b30/a0', false];
        yield 'unrelated branch' => ['a1/a0', false];
    }

    #[Test]
    public function rangeBoundsAreTheExpectedLiterals(): void
    {
        $node = NodeSortPath::fromString('a0/b3');
        self::assertSame('a0/b3/', $node->rangeStart());
        self::assertSame('a0/b30', $node->rangeEnd());
    }

    #[Test]
    public function rangeAccessorsRejectTheRootPath(): void
    {
        $this->expectExceptionCode(1775980003);
        NodeSortPath::root()->rangeStart();
    }

    #[Test]
    public function anOverLongKeyIsRecoverableByRebalancing(): void
    {
        $longKey = NodeSortPath::fromString('a0/' . str_repeat('z', NodeSortPath::MAX_KEY_LENGTH + 1));
        self::assertTrue($longKey->exceedsMaxKeyLength());
        // it still fits the column, so the rebalance gets a chance to run before anything is written
        $longKey->assertFitsColumn();
    }

    #[Test]
    public function anOverLongPathIsNotRecoverableAndThrows(): void
    {
        // short keys throughout, just too many levels - no rebalance can shorten this
        $tooDeep = NodeSortPath::fromString(substr(str_repeat('a0/', NodeSortPath::MAX_LENGTH), 0, NodeSortPath::MAX_LENGTH + 1));
        self::assertFalse($tooDeep->exceedsMaxKeyLength());

        $this->expectExceptionCode(1775980001);
        $tooDeep->assertFitsColumn();
    }

    /**
     * Ordering the paths as raw bytes must yield depth-first document order. This is the property the
     * VARBINARY column type exists to guarantee: under the server default case-insensitive collations
     * 'Z' would equal 'z' and this ordering would silently fall apart.
     */
    #[Test]
    public function byteOrderingYieldsDocumentOrder(): void
    {
        $paths = ['a0/b3/ZzV', 'a1', 'a0', 'a0/b3', 'a0/b30', 'a0/a0', 'a0/b3/a0'];
        usort($paths, static fn (string $a, string $b): int => strcmp($a, $b));

        self::assertSame([
            'a0',
            'a0/a0',
            'a0/b3',
            'a0/b3/ZzV',
            'a0/b3/a0',
            'a0/b30',
            'a1',
        ], $paths);
    }
}
