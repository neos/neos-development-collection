<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use PHPUnit\Framework\TestCase;

class NodeTagsTest extends TestCase
{

    /**
     * @test
     */
    public function createEmptyCreatesEmptyInstance(): void
    {
        self::assertSame([], iterator_to_array(NodeTags::createEmpty()));
    }

    /**
     * @test
     */
    public function createFailsIfTheSameTagIsContainedInInheritedAndExplicitSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('foos', 'bar'));
    }

    /**
     * @test
     */
    public function withoutReturnsSameInstanceIfSpecifiedTagIsNotContained(): void
    {
        $tags = NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('baz'));
        self::assertSame($tags, $tags->without(SubtreeTag::fromString('foos')));
    }

    /**
     * @test
     */
    public function withoutReturnsInstanceWithoutSpecifiedTag(): void
    {
        $tags = NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('baz'))
            ->without(SubtreeTag::fromString('bar'))
            ->without(SubtreeTag::fromString('baz'));
        self::assertSame(['foo'], $tags->toStringArray());
    }

    public static function withoutInheritedDataProvider(): iterable
    {
        yield 'both empty' => ['tags' => [], 'inheritedTags' => [], 'expectedResult' => []];
        yield 'no explicit' => ['tags' => [], 'inheritedTags' => ['foos'], 'expectedResult' => []];
        yield 'no inherited' => ['tags' => ['foo', 'bar'], 'inheritedTags' => [], 'expectedResult' => ['foo', 'bar']];
        yield 'both' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos', 'bars'], 'expectedResult' => ['foo', 'bar']];
    }


    /**
     * @test
     * @dataProvider withoutInheritedDataProvider
     */
    public function withoutInheritedTests(array $tags, array $inheritedTags, array $expectedResult): void
    {
        self::assertSame($expectedResult, NodeTags::create(SubtreeTags::fromStrings(...$tags), SubtreeTags::fromStrings(...$inheritedTags))->withoutInherited()->toStringArray());
    }

    public static function onlyInheritedDataProvider(): iterable
    {
        yield 'both empty' => ['tags' => [], 'inheritedTags' => [], 'expectedResult' => []];
        yield 'no explicit' => ['tags' => [], 'inheritedTags' => ['foos'], 'expectedResult' => ['foos']];
        yield 'no inherited' => ['tags' => ['foo', 'bar'], 'inheritedTags' => [], 'expectedResult' => []];
        yield 'both' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos', 'bars'], 'expectedResult' => ['foos', 'bars']];
    }


    /**
     * @test
     * @dataProvider onlyInheritedDataProvider
     */
    public function onlyInheritedTests(array $tags, array $inheritedTags, array $expectedResult): void
    {
        self::assertSame($expectedResult, NodeTags::create(SubtreeTags::fromStrings(...$tags), SubtreeTags::fromStrings(...$inheritedTags))->onlyInherited()->toStringArray());
    }

    public static function isEmptyDataProvider(): iterable
    {
        yield 'both empty' => ['tags' => [], 'inheritedTags' => [], 'expectedResult' => true];
        yield 'no explicit' => ['tags' => [], 'inheritedTags' => ['foos'], 'expectedResult' => false];
        yield 'no inherited' => ['tags' => ['foo', 'bar'], 'inheritedTags' => [], 'expectedResult' => false];
        yield 'both' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos'], 'expectedResult' => false];
    }


    /**
     * @test
     * @dataProvider isEmptyDataProvider
     */
    public function isEmptyTests(array $tags, array $inheritedTags, bool $expectedResult): void
    {
        self::assertSame($expectedResult, NodeTags::create(SubtreeTags::fromStrings(...$tags), SubtreeTags::fromStrings(...$inheritedTags))->isEmpty());
    }

    public static function countDataProvider(): iterable
    {
        yield 'both empty' => ['tags' => [], 'inheritedTags' => [], 'expectedResult' => 0];
        yield 'no explicit' => ['tags' => [], 'inheritedTags' => ['foos'], 'expectedResult' => 1];
        yield 'no inherited' => ['tags' => ['foo', 'bar'], 'inheritedTags' => [], 'expectedResult' => 2];
        yield 'both' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos'], 'expectedResult' => 3];
    }


    /**
     * @test
     * @dataProvider countDataProvider
     */
    public function countTests(array $tags, array $inheritedTags, int $expectedResult): void
    {
        self::assertSame($expectedResult, NodeTags::create(SubtreeTags::fromStrings(...$tags), SubtreeTags::fromStrings(...$inheritedTags))->count());
    }

    public static function containDataProvider(): iterable
    {
        yield 'both empty' => ['tags' => [], 'inheritedTags' => [], 'tag' => 'foo', 'expectedResult' => false];
        yield 'not contained' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos'], 'tag' => 'baz', 'expectedResult' => false];
        yield 'is contained in explicit' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos'], 'tag' => 'bar', 'expectedResult' => true];
        yield 'is contained in inherited' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos'], 'tag' => 'foos', 'expectedResult' => true];
    }


    /**
     * @test
     * @dataProvider containDataProvider
     */
    public function containTests(array $tags, array $inheritedTags, string $tag, bool $expectedResult): void
    {
        self::assertSame($expectedResult, NodeTags::create(SubtreeTags::fromStrings(...$tags), SubtreeTags::fromStrings(...$inheritedTags))->contain(SubtreeTag::fromString($tag)));
    }

    public static function allDataProvider(): iterable
    {
        yield 'both empty' => ['tags' => [], 'inheritedTags' => [], 'expectedResult' => []];
        yield 'no explicit' => ['tags' => [], 'inheritedTags' => ['foos'], 'expectedResult' => ['foos']];
        yield 'no inherited' => ['tags' => ['foo', 'bar'], 'inheritedTags' => [], 'expectedResult' => ['foo', 'bar']];
        yield 'both' => ['tags' => ['foo', 'bar'], 'inheritedTags' => ['foos'], 'expectedResult' => ['foo', 'bar', 'foos']];
    }


    /**
     * @test
     * @dataProvider allDataProvider
     */
    public function allTests(array $tags, array $inheritedTags, array $expectedResult): void
    {
        self::assertSame($expectedResult, NodeTags::create(SubtreeTags::fromStrings(...$tags), SubtreeTags::fromStrings(...$inheritedTags))->all()->toStringArray());
    }

    /**
     * @test
     */
    public function mapAppliesCallback(): void
    {
        $result = NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('baz'))->map(static fn (SubtreeTag $tag, bool $inherited) => strtoupper($tag->value) . ($inherited ? 'i' : 'e'));
        self::assertSame(['FOOe', 'BARe', 'BAZi'], $result);
    }

    /**
     * @test
     */
    public function toStringArrayReturnsEmptyArrayForEmptySet(): void
    {
        self::assertSame([], NodeTags::createEmpty()->toStringArray());
    }

    /**
     * @test
     */
    public function toStringArrayReturnsTagsAsStrings(): void
    {
        self::assertSame(['foo', 'bar', 'baz'], NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('baz'))->toStringArray());
    }

    /**
     * @test
     */
    public function canBeIterated(): void
    {
        $result = [];
        foreach (NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('baz')) as $tag) {
            $result[] = $tag->value;
        }
        self::assertSame(['foo', 'bar', 'baz'], $result);
    }

    /**
     * @test
     */
    public function canBeSerialized(): void
    {
        self::assertSame('{"foo":true,"bar":true,"baz":null}', json_encode(NodeTags::create(SubtreeTags::fromStrings('foo', 'bar'), SubtreeTags::fromStrings('baz'))));
    }

}
