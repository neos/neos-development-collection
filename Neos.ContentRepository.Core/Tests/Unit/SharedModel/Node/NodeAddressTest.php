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
    public static function urlCompatibleSerialization(): iterable
    {
        yield 'no dimensions' => [
            'nodeAddress' => NodeAddress::create(
                ContentRepositoryId::fromString('default'),
                WorkspaceName::forLive(),
                DimensionSpacePoint::createWithoutDimensions(),
                NodeAggregateId::fromString('marcus-heinrichus')
            ),
            'serialized' => 'default/live//marcus-heinrichus'
        ];

        yield 'one dimension' => [
            'nodeAddress' => NodeAddress::create(
                ContentRepositoryId::fromString('default'),
                WorkspaceName::fromString('user-mh'),
                DimensionSpacePoint::fromArray(['language' => 'de']),
                NodeAggregateId::fromString('79e69d1c-b079-4535-8c8a-37e76736c445')
            ),
            'serialized' => 'default/user-mh/bGFuZ3VhZ2U9ZGU=/79e69d1c-b079-4535-8c8a-37e76736c445'
        ];

        yield 'two dimensions' => [
            'nodeAddress' => NodeAddress::create(
                ContentRepositoryId::fromString('second'),
                WorkspaceName::fromString('user-mh'),
                DimensionSpacePoint::fromArray(['language' => 'en_US', 'audience' => 'nice people']),
                NodeAggregateId::fromString('my-node-id')
            ),
            'serialized' => 'second/user-mh/bGFuZ3VhZ2U9ZW5fVVMmYXVkaWVuY2U9bmljZStwZW9wbGU=/my-node-id'
        ];
    }

    /**
     * @dataProvider urlCompatibleSerialization
     */
    public function testUrlCompatibleSerialization(NodeAddress $nodeAddress, string $expected): void
    {
        self::assertEquals($expected, $nodeAddress->toUriString());
    }

    /**
     * @dataProvider urlCompatibleSerialization
     */
    public function testUrlCompatibleDeserialization(NodeAddress $expectedNodeAddress, string $encoded): void
    {
        $nodeAddress = NodeAddress::fromUriString($encoded);
        self::assertInstanceOf(NodeAddress::class, $nodeAddress);
        self::assertTrue($expectedNodeAddress->equals($nodeAddress));
    }
}
