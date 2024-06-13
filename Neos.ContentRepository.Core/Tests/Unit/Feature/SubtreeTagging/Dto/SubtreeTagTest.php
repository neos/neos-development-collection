<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Feature\SubtreeTagging\Dto;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use PHPUnit\Framework\TestCase;

class SubtreeTagTest extends TestCase
{

    /**
     * @test
     */
    public function fromStringSupportsUUIDs(): void
    {
        $uuid = '2281f529-d769-4084-9bdb-ea0f89356667';
        self::assertSame($uuid, SubtreeTag::fromString($uuid)->value);
    }

    /**
     * @test
     */
    public function fromStringFailsIfStringContainsColon(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('invalid:tag');
    }

    /**
     * @test
     */
    public function fromStringFailsIfStringContainsUpperCaseCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('invalidTag');
    }

    /**
     * @test
     */
    public function fromStringFailsIfStringContainsSpecialCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('invälid');
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfTagValuesMatch(): void
    {
        self::assertTrue(SubtreeTag::fromString('some-tag')->equals(SubtreeTag::fromString('some-tag')));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfTagValuesDontMatch(): void
    {
        self::assertFalse(SubtreeTag::fromString('some-tag')->equals(SubtreeTag::fromString('some_tag')));
    }

    /**
     * @test
     */
    public function canBeSerialized(): void
    {
        self::assertSame('"some-tag"', json_encode(SubtreeTag::fromString('some-tag')));
    }

}
