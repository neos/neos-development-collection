<?php

declare(strict_types=1);

namespace Neos\ContentGraph\Tests\Unit;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use PHPUnit\Framework\TestCase;

class ContentStreamLayersTest extends TestCase
{
    /** @test */
    public function invalidLayer()
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentStreamLayer::fromInt(0);
    }

    /** @test */
    public function invalidLayer2()
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentStreamLayer::fromInt(-1);
    }

    /** @test */
    public function invalidEmptyLayers()
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentStreamLayers::fromArray([]);
    }

    /** @test */
    public function invalidLayerNumbers()
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentStreamLayers::fromArray([0, -1]);
    }

    /** @test */
    public function getRootLayer()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame(1, $layers->getRootLayer()->value);

        $layers2 = ContentStreamLayers::fromArray([1]);
        self::assertSame(1, $layers2->getRootLayer()->value);
    }

    /** @test */
    public function getWriteLayer()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame(6, $layers->getWriteLayer()->value);

        $layer2 = ContentStreamLayers::fromArray([1, 2]);
        self::assertSame(2, $layer2->getWriteLayer()->value);

        $layers3 = ContentStreamLayers::fromArray([1]);
        self::assertSame(1, $layers3->getWriteLayer()->value);
    }

    /** @test */
    public function getParentReadLayer()
    {
        $layers = ContentStreamLayers::fromArray([1]);
        self::assertNull($layers->getParentReadLayer());

        $layers1 = ContentStreamLayers::fromArray([1, 2]);
        self::assertSame(1, $layers1->getParentReadLayer()->value);

        $layers2 = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame(5, $layers2->getParentReadLayer()->value);
    }

    /** @test */
    public function getParentReadLayers()
    {
        $layers = ContentStreamLayers::fromArray([1]);
        self::assertNull($layers->getParentReadLayers());

        $layers1 = ContentStreamLayers::fromArray([1, 2]);
        // Array keys are preserved for indexing
        self::assertTrue($layers1->contain(ContentStreamLayer::fromInt(1)));
        self::assertSame([1], $layers1->getParentReadLayers()->toIntArray());

        $layers2 = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertSame([1, 3, 5], $layers2->getParentReadLayers()->toIntArray());
        self::assertSame([1, 3], $layers2->getParentReadLayers()->getParentReadLayers()->toIntArray());

    }

    /** @test */
    public function equalsSingle()
    {
        $layers = ContentStreamLayers::fromArray([3, 6, 1, 5]);
        self::assertFalse($layers->equalsSingle(ContentStreamLayer::fromInt(1)));
        self::assertFalse($layers->equalsSingle(ContentStreamLayer::fromInt(6)));

        $layers2 = ContentStreamLayers::fromArray([1]);
        self::assertTrue($layers2->equalsSingle(ContentStreamLayer::fromInt(1)));
        self::assertFalse($layers2->equalsSingle(ContentStreamLayer::fromInt(2)));
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

    /** @test */
    public function ignoresDuplicates()
    {
        $layers = ContentStreamLayers::fromArray([1, 3, 1, 3]);
        self::assertSame([1, 3], $layers->toIntArray());
        self::assertSame([1], $layers->getParentReadLayers()->toIntArray());
        self::assertCount(2, $layers->items);
    }
}
