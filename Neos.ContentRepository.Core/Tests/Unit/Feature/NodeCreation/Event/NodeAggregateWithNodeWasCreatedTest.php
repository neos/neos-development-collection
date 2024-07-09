<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Feature\NodeCreation\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;

class NodeAggregateWithNodeWasCreatedTest extends TestCase
{
    /**
     * @param array<string,mixed> $payload
     * @dataProvider eventPayloadProviderWithLegacySucceedingNodeAggregateIdFormat
     */
    public function testFromArray(array $payload, NodeAggregateWithNodeWasCreated $expectedEvent): void
    {
        self::assertEquals($expectedEvent, NodeAggregateWithNodeWasCreated::fromArray($payload));
    }

    /**
     * The coveredDimensionSpacePoints and succeedingNodeAggregateId were replaced with `InterdimensionalSiblings`.
     * A runtime migration was added to {@see NodeAggregateWithNodeWasCreated::fromArray()} to handle the legacy format.
     *
     * Via #4961: https://github.com/neos/neos-development-collection/pull/4961
     *
     * Included in April 2024
     *
     * @return iterable<mixed>
     */
    public static function eventPayloadProviderWithLegacySucceedingNodeAggregateIdFormat(): iterable
    {
        yield 'legacy coveredDimensionSpacePoints succeedingNodeAggregateId fields are transformed' => [
            [
                'contentStreamId' => 'd1afafaf-e078-4b44-a9a2-077094e8e45b',
                'nodeAggregateId' => '872344d1-7da5-4b17-a1f0-0138987431a9',
                'nodeTypeName' => 'Vendor.Site:Document.Page',
                'originDimensionSpacePoint' =>
                    [
                        'language' => 'pl',
                    ],
                'coveredDimensionSpacePoints' =>
                    [
                        [
                            'language' => 'pl',
                        ],
                    ],
                'parentNodeAggregateId' => '6b6e1251-4346-494f-ac56-526a30a5741d',
                'nodeName' => null,
                'initialPropertyValues' => [],
                'nodeAggregateClassification' => 'regular',
                'succeedingNodeAggregateId' => '30ef3082-e37f-4346-83cf-45ed0249381f',
                'workspaceName' => 'user-soee',
            ],
            new NodeAggregateWithNodeWasCreated(
                WorkspaceName::fromString('user-soee'),
                ContentStreamId::fromString('d1afafaf-e078-4b44-a9a2-077094e8e45b'),
                NodeAggregateId::fromString('872344d1-7da5-4b17-a1f0-0138987431a9'),
                NodeTypeName::fromString('Vendor.Site:Document.Page'),
                OriginDimensionSpacePoint::fromArray(['language' => 'pl']),
                new InterdimensionalSiblings(
                    new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray(['language' => 'pl']),
                        NodeAggregateId::fromString('30ef3082-e37f-4346-83cf-45ed0249381f')
                    )
                ),
                NodeAggregateId::fromString('6b6e1251-4346-494f-ac56-526a30a5741d'),
                null,
                SerializedPropertyValues::createEmpty(),
                NodeAggregateClassification::CLASSIFICATION_REGULAR
            )
        ];

        yield 'legacy succeedingNodeAggregateId field is null' => [
            [
                'contentStreamId' => 'd1afafaf-e078-4b44-a9a2-077094e8e45b',
                'nodeAggregateId' => '872344d1-7da5-4b17-a1f0-0138987431a9',
                'nodeTypeName' => 'Vendor.Site:Document.Page',
                'originDimensionSpacePoint' =>
                    [
                        'language' => 'pl',
                    ],
                'coveredDimensionSpacePoints' =>
                    [
                        [
                            'language' => 'pl',
                        ],
                    ],
                'parentNodeAggregateId' => '6b6e1251-4346-494f-ac56-526a30a5741d',
                'nodeName' => null,
                'initialPropertyValues' => [],
                'nodeAggregateClassification' => 'regular',
                'succeedingNodeAggregateId' => null,
                'workspaceName' => 'user-soee',
            ],
            new NodeAggregateWithNodeWasCreated(
                WorkspaceName::fromString('user-soee'),
                ContentStreamId::fromString('d1afafaf-e078-4b44-a9a2-077094e8e45b'),
                NodeAggregateId::fromString('872344d1-7da5-4b17-a1f0-0138987431a9'),
                NodeTypeName::fromString('Vendor.Site:Document.Page'),
                OriginDimensionSpacePoint::fromArray(['language' => 'pl']),
                new InterdimensionalSiblings(
                    new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray(['language' => 'pl']),
                        null
                    )
                ),
                NodeAggregateId::fromString('6b6e1251-4346-494f-ac56-526a30a5741d'),
                null,
                SerializedPropertyValues::createEmpty(),
                NodeAggregateClassification::CLASSIFICATION_REGULAR
            )
        ];
    }
}
