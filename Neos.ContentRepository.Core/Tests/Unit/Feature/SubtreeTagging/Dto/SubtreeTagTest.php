<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Feature\SubtreeTagging\Dto;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SubtreeTagTest extends TestCase
{

    #[Test]
    public function fromStringSupportsUUIDs(): void
    {
        // leading and trailing digits are allowed!
        $uuid = '2281f529-d769-4084-9bdb-ea0f89356667';
        self::assertSame($uuid, SubtreeTag::fromString($uuid)->value);
    }

    #[Test]
    public function fromStringFailsIfStringContainsColon(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('invalid:tag');
    }

    #[Test]
    public function fromStringFailsIfStringContainsUpperCaseCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('invalidTag');
    }

    #[Test]
    public function fromStringFailsIfStringContainsOnlyNumericCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('12345');
    }

    #[Test]
    public function fromStringFailsIfStringContainsOnlyNumericWithLeadingZeroCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('007');
    }

    #[Test]
    public function fromStringFailsIfStringContainsSpecialCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SubtreeTag::fromString('invälid');
    }

    #[Test]
    public function equalsReturnsTrueIfTagValuesMatch(): void
    {
        self::assertTrue(SubtreeTag::fromString('some-tag')->equals(SubtreeTag::fromString('some-tag')));
    }

    #[Test]
    public function equalsReturnsFalseIfTagValuesDontMatch(): void
    {
        self::assertFalse(SubtreeTag::fromString('some-tag')->equals(SubtreeTag::fromString('some_tag')));
    }

    #[Test]
    public function canBeSerialized(): void
    {
        self::assertSame('"some-tag"', json_encode(SubtreeTag::fromString('some-tag')));
    }

}
