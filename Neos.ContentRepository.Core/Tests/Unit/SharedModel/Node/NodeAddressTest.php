<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;

class NodeAddressTest extends TestCase
{
    public static function jsonSerialization(): iterable
    {
        yield 'no dimensions' => [
            'nodeAddress' => NodeAddress::create(
                ContentRepositoryId::fromString('default'),
                WorkspaceName::forLive(),
                DimensionSpacePoint::createWithoutDimensions(),
                NodeAggregateId::fromString('marcus-heinrichus')
            ),
            'serialized' => '{"contentRepositoryId":"default","workspaceName":"live","dimensionSpacePoint":[],"aggregateId":"marcus-heinrichus"}'
        ];

        yield 'one dimension' => [
            'nodeAddress' => NodeAddress::create(
                ContentRepositoryId::fromString('default'),
                WorkspaceName::fromString('user-mh'),
                DimensionSpacePoint::fromArray(['language' => 'de']),
                NodeAggregateId::fromString('79e69d1c-b079-4535-8c8a-37e76736c445')
            ),
            'serialized' => '{"contentRepositoryId":"default","workspaceName":"user-mh","dimensionSpacePoint":{"language":"de"},"aggregateId":"79e69d1c-b079-4535-8c8a-37e76736c445"}'
        ];

        yield 'two dimensions' => [
            'nodeAddress' => NodeAddress::create(
                ContentRepositoryId::fromString('second'),
                WorkspaceName::fromString('user-mh'),
                DimensionSpacePoint::fromArray(['language' => 'en_US', 'audience' => 'nice people']),
                NodeAggregateId::fromString('my-node-id')
            ),
            'serialized' => '{"contentRepositoryId":"second","workspaceName":"user-mh","dimensionSpacePoint":{"language":"en_US","audience":"nice people"},"aggregateId":"my-node-id"}'
        ];
    }

    /**
     * @dataProvider jsonSerialization
     * @test
     */
    public function serialization(NodeAddress $nodeAddress, string $expected): void
    {
        self::assertEquals($expected, $nodeAddress->toJson());
    }

    /**
     * @dataProvider jsonSerialization
     * @test
     */
    public function deserialization(NodeAddress $expectedNodeAddress, string $encoded): void
    {
        $nodeAddress = NodeAddress::fromJsonString($encoded);
        self::assertInstanceOf(NodeAddress::class, $nodeAddress);
        self::assertTrue($expectedNodeAddress->equals($nodeAddress));
    }
}
