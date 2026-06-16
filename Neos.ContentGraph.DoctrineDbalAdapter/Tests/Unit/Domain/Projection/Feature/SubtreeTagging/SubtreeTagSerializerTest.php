<?php

declare(strict_types=1);

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging\SubtreeTagSerializer;
use PHPUnit\Framework\TestCase;

class SubtreeTagSerializerTest extends TestCase
{
    public static function equalityExamples(): iterable
    {
        yield 'L ' . __LINE__ => [
            [],
            []
        ];

        yield 'L ' . __LINE__ => [
            ['foo' => true],
            ['foo' => true]
        ];

        yield 'L ' . __LINE__ => [
            ['foo' => true, 'bar' => true],
            ['bar' => true, 'foo' => true]
        ];
    }

    public static function inEqualityExamples(): iterable
    {
        yield 'L ' . __LINE__ => [
            ['foo' => true],
            []
        ];

        yield 'L ' . __LINE__ => [
            [],
            ['foo' => true]
        ];

        yield 'L ' . __LINE__ => [
            ['foo' => null],
            ['foo' => true]
        ];

        yield 'L ' . __LINE__ => [
            ['foo' => true, 'bar' => true],
            ['foo' => true, 'other' => true]
        ];
    }

    /**
     * @test
     * @dataProvider equalityExamples
     */
    public function equal(array $first, array $second): void
    {
        self::assertTrue(
            SubtreeTagSerializer::subtreeTagsEqual(
                $first,
                $second
            )
        );
    }

    /**
     * @test
     * @dataProvider inEqualityExamples
     */
    public function inEqual(array $first, array $second): void
    {
        self::assertFalse(
            SubtreeTagSerializer::subtreeTagsEqual(
                $first,
                $second
            )
        );
    }

    /** @test */
    public function decodeSubtreeTags(): void
    {
        self::assertSame(
            [],
            SubtreeTagSerializer::decodeSubtreeTags('{}')
        );

        self::assertSame(
            [],
            SubtreeTagSerializer::decodeSubtreeTags(null)
        );

        self::assertSame(
            ['foo' => true],
            SubtreeTagSerializer::decodeSubtreeTags('{"foo":true}')
        );
    }

    /** @test */
    public function encodeSubtreeTags(): void
    {
        self::assertSame(
            '{}',
            SubtreeTagSerializer::encodeSubtreeTags([])
        );

        self::assertSame(
            '{"foo":true}',
            SubtreeTagSerializer::encodeSubtreeTags(['foo' => true])
        );
    }
}
