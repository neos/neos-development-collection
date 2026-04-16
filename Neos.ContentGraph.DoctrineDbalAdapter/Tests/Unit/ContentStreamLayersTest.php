<?php

declare(strict_types=1);

namespace Neos\ContentGraph\Tests\Unit;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use PHPUnit\Framework\TestCase;

class ContentStreamLayersTest extends TestCase
{
    /** @test */
    public function getWriteLayer()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame(6, $layers->getWriteLayer()->value);

        $layers2 = ContentStreamLayers::fromArray([1]);
        self::assertSame(1, $layers2->getWriteLayer()->value);
    }

    /** @test */
    public function getParentReadLayer()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame(5, $layers->getParentReadLayer()->value);

        $layers2 = ContentStreamLayers::fromArray([1]);
        self::assertNull($layers2->getParentReadLayer());
    }

    /** @test */
    public function equals()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertFalse($layers->equals(ContentStreamLayer::fromInt(1)));
        self::assertFalse($layers->equals(ContentStreamLayer::fromInt(6)));

        $layers2 = ContentStreamLayers::fromArray([1]);
        self::assertTrue($layers2->equals(ContentStreamLayer::fromInt(1)));
        self::assertFalse($layers2->equals(ContentStreamLayer::fromInt(2)));
    }

    /** @test */
    public function contain()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertTrue($layers->contain(ContentStreamLayer::fromInt(1)));
        self::assertTrue($layers->contain(ContentStreamLayer::fromInt(5)));
        self::assertFalse($layers->contain(ContentStreamLayer::fromInt(8)));

        $layers2 = ContentStreamLayers::fromArray([1]);
        self::assertTrue($layers2->contain(ContentStreamLayer::fromInt(1)));
        self::assertFalse($layers2->contain(ContentStreamLayer::fromInt(2)));
    }

    /** @test */
    public function toIntArray()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame([1, 3, 5, 6], $layers->toIntArray());
    }
}
