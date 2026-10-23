<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\Domain\Service\NodeDuplication;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Neos\Domain\Service\NodeDuplication\NodeAggregateIdMapping;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NodeAggregateIdMappingTest extends TestCase
{
    #[Test]
    public function constructWithNumericIds()
    {
        $ids = NodeAggregateIdMapping::fromArray(['123' => '456']);
        self::assertNull($ids->getNewNodeAggregateId(NodeAggregateId::fromString('456')));
        self::assertSame('456', $ids->getNewNodeAggregateId(NodeAggregateId::fromString('123'))?->value);

        $ids = NodeAggregateIdMapping::createEmpty()->withNewNodeAggregateId(
            NodeAggregateId::fromString('123'),
            NodeAggregateId::fromString('456')
        );
        self::assertSame('456', $ids->getNewNodeAggregateId(NodeAggregateId::fromString('123'))?->value);
    }
}
