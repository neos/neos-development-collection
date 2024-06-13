<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Feature\SubtreeTagging\Dto;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use PHPUnit\Framework\TestCase;

class SubtreeTagsTest extends TestCase
{

    /**
     * @test
     */
    public function createEmptyCreatesEmptyInstance(): void
    {
        self::assertSame([], iterator_to_array(SubtreeTags::createEmpty()));
    }

    /**
     * @test
     */
    public function fromStringsRemovesDuplicates(): void
    {
        self::assertSame(['foo', 'bar'], SubtreeTags::fromStrings('foo', 'bar', 'foo')->toStringArray());
    }

    /**
     * @test
     */
    public function withoutReturnsSameInstanceIfSpecifiedTagIsNotContained(): void
    {
        $tags = SubtreeTags::fromStrings('foo', 'bar');
        self::assertSame($tags, $tags->without(SubtreeTag::fromString('baz')));
    }

    /**
     * @test
     */
    public function withoutReturnsInstanceWithoutSpecifiedTag(): void
    {
        $tags = SubtreeTags::fromStrings('foo', 'bar')
            ->without(SubtreeTag::fromString('foo'));
        self::assertSame(['bar'], $tags->toStringArray());
    }

    /**
     * @test
     */
    public function fromArrayFailsIfArrayContainsString(): void
    {
        $this->expectException(\TypeError::class);
        SubtreeTags::fromArray([SubtreeTag::fromString('foo'), 'bar']);
    }

    /**
     * @test
     */
    public function fromArrayReturnsInstance(): void
    {
        self::assertSame(['foo', 'bar'], SubtreeTags::fromArray([SubtreeTag::fromString('foo'), SubtreeTag::fromString('bar'), SubtreeTag::fromString('foo')])->toStringArray());
    }

    public static function isEmptyDataProvider(): iterable
    {
        yield 'empty' => ['tags' => [], 'expectedResult' => true];
        yield 'single tag' => ['tags' => ['foo'], 'expectedResult' => false];
        yield 'four tags with one duplicate' => ['tags' => ['foo', 'bar', 'baz', 'foo'], 'expectedResult' => false];
    }


    /**
     * @test
     * @dataProvider isEmptyDataProvider
     */
    public function isEmptyTests(array $tags, bool $expectedResult): void
    {
        self::assertSame($expectedResult, SubtreeTags::fromStrings(...$tags)->isEmpty());
    }

    public static function countDataProvider(): iterable
    {
        yield 'empty' => ['tags' => [], 'expectedResult' => 0];
        yield 'single tag' => ['tags' => ['foo'], 'expectedResult' => 1];
        yield 'four tags with one duplicate' => ['tags' => ['foo', 'bar', 'baz', 'foo'], 'expectedResult' => 3];
    }


    /**
     * @test
     * @dataProvider countDataProvider
     */
    public function countTests(array $tags, int $expectedResult): void
    {
        self::assertSame($expectedResult, SubtreeTags::fromStrings(...$tags)->count());
    }

    public static function containDataProvider(): iterable
    {
        yield 'empty' => ['tags' => [], 'tag' => 'foo', 'expectedResult' => false];
        yield 'not contained' => ['tags' => ['foo', 'bar'], 'tag' => 'baz', 'expectedResult' => false];
        yield 'is contained' => ['tags' => ['foo', 'bar'], 'tag' => 'bar', 'expectedResult' => true];
    }


    /**
     * @test
     * @dataProvider containDataProvider
     */
    public function containTests(array $tags, string $tag, bool $expectedResult): void
    {
        self::assertSame($expectedResult, SubtreeTags::fromStrings(...$tags)->contain(SubtreeTag::fromString($tag)));
    }


    public static function intersectionDataProvider(): iterable
    {
        yield 'empty' => ['tags1' => [], 'tags2' => [], 'expectedResult' => []];
        yield 'one empty' => ['tags1' => [], 'tags2' => ['foo'], 'expectedResult' => []];
        yield 'two empty' => ['tags1' => ['foo'], 'tags2' => [], 'expectedResult' => []];
        yield 'no intersection' => ['tags1' => ['foo', 'bar'], 'tags2' => ['baz', 'foos'], 'expectedResult' => []];
        yield 'with intersection' => ['tags1' => ['foo', 'bar', 'baz'], 'tags2' => ['baz', 'bars', 'foo'], 'expectedResult' => ['foo', 'baz']];
        yield 'with intersection reversed' => ['tags1' => ['baz', 'bars', 'foo'], 'tags2' => ['foo', 'bar', 'baz'], 'expectedResult' => ['baz', 'foo']];
    }


    /**
     * @test
     * @dataProvider intersectionDataProvider
     */
    public function intersectionTests(array $tags1, array $tags2, array $expectedResult): void
    {
        self::assertSame($expectedResult, SubtreeTags::fromStrings(...$tags1)->intersection(SubtreeTags::fromStrings(...$tags2))->toStringArray());
    }

    public static function mergeDataProvider(): iterable
    {
        yield 'empty' => ['tags1' => [], 'tags2' => [], 'expectedResult' => []];
        yield 'one empty' => ['tags1' => [], 'tags2' => ['foo'], 'expectedResult' => ['foo']];
        yield 'two empty' => ['tags1' => ['foo'], 'tags2' => [], 'expectedResult' => ['foo']];
        yield 'no intersection' => ['tags1' => ['foo', 'bar'], 'tags2' => ['baz', 'foos'], 'expectedResult' => ['foo', 'bar', 'baz', 'foos']];
        yield 'with intersection' => ['tags1' => ['foo', 'bar', 'baz'], 'tags2' => ['baz', 'bars', 'foo'], 'expectedResult' => ['foo', 'bar', 'baz', 'bars']];
    }


    /**
     * @test
     * @dataProvider mergeDataProvider
     */
    public function mergeTests(array $tags1, array $tags2, array $expectedResult): void
    {
        self::assertSame($expectedResult, SubtreeTags::fromStrings(...$tags1)->merge(SubtreeTags::fromStrings(...$tags2))->toStringArray());
    }

    /**
     * @test
     */
    public function mapAppliesCallback(): void
    {
        $result = SubtreeTags::fromStrings('foo', 'bar', 'baz')->map(static fn (SubtreeTag $tag) => strtoupper($tag->value));
        self::assertSame(['FOO', 'BAR', 'BAZ'], $result);
    }

    /**
     * @test
     */
    public function toStringArrayReturnsEmptyArrayForEmptySet(): void
    {
        self::assertSame([], SubtreeTags::createEmpty()->toStringArray());
    }

    /**
     * @test
     */
    public function toStringArrayReturnsTagsAsStrings(): void
    {
        self::assertSame(['foo', 'bar'], SubtreeTags::fromStrings('foo', 'bar')->toStringArray());
    }

    /**
     * @test
     */
    public function canBeSerialized(): void
    {
        self::assertSame('["foo","bar"]', json_encode(SubtreeTags::fromStrings('foo', 'bar', 'foo')));
    }
}
