<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Node;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use PHPUnit\Framework\TestCase;

class NodeAggregateIdsTest extends TestCase
{
    /**
     * @test
     */
    public function constructWithNumericIds()
    {
        $ids = NodeAggregateIds::fromArray(['123']);
        self::assertSame(['123'], $ids->toStringArray());
        self::assertTrue($ids->contain(NodeAggregateId::fromString('123')));

        $ids = NodeAggregateIds::fromArray(['abc', '123']);
        self::assertSame(['abc', '123'], $ids->toStringArray());
        self::assertTrue($ids->contain(NodeAggregateId::fromString('123')));
        self::assertTrue($ids->contain(NodeAggregateId::fromString('abc')));

        $ids = NodeAggregateIds::fromArray(['123', 'abc']);
        self::assertSame(['123', 'abc'], $ids->toStringArray());
        self::assertTrue($ids->contain(NodeAggregateId::fromString('123')));
    }

    /**
     * @test
     */
    public function jsonRepresentation()
    {
        // FIXME, why do we encode the collection as object and not as list?
        $ids = NodeAggregateIds::fromArray(['abc', 'def']);
        self::assertSame('{"abc":"abc","def":"def"}', $ids->toJson());
    }
}
