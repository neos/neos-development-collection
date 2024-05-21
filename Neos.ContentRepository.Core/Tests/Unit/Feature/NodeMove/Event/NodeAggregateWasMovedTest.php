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

namespace Neos\ContentRepository\Core\Tests\Unit\Feature\NodeMove\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;


class NodeAggregateWasMovedTest extends TestCase
{
    /**
     * @param array<string,mixed> $payload
     * @dataProvider eventPayloadProvider
     */
    public function testFromArray(array $payload, NodeAggregateWasMoved $expectedEvent): void
    {
        self::assertEquals($expectedEvent, NodeAggregateWasMoved::fromArray($payload));
    }

    /**
     * @return \Traversable<string,array<string,mixed>>
     */
    public static function eventPayloadProvider(): \Traversable
    {
        yield 'singleNodeMoveMappingWithSibling' => [
            'payload' => [
                 'contentStreamId' => '90bcfbf8-c444-48f8-9911-ba0954ac795a',
                 'nodeAggregateId' => '30ef3082-e37f-4346-83cf-45ed0249381f',
                 'nodeMoveMappings' => [
                     [
                         'movedNodeOrigin' => [
                             'language' => 'pl'
                         ],
                         'newLocations' => [
                             [
                                 'coveredDimensionSpacePoint' => [
                                     'language' => 'pl'
                                 ],
                                 'newSucceedingSibling' => [
                                     'nodeAggregateId' => '7db24575-1227-4c7c-87ff-7aaa98532a94',
                                     'originDimensionSpacePoint' => [
                                         'language' => 'pl'
                                     ],
                                     'parentNodeAggregateId' => '6b6e1251-4346-494f-ac56-526a30a5741d',
                                     'parentOriginDimensionSpacePoint' => [
                                         'language' => 'pl'
                                     ]
                                 ]
                             ]
                         ]
                     ],
                 ],
                'workspaceName' => 'user-soee'
            ],
            'expectedEvent' => new NodeAggregateWasMoved(
                WorkspaceName::fromString('user-soee'),
                ContentStreamId::fromString('90bcfbf8-c444-48f8-9911-ba0954ac795a'),
                NodeAggregateId::fromString('30ef3082-e37f-4346-83cf-45ed0249381f'),
                NodeAggregateId::fromString('6b6e1251-4346-494f-ac56-526a30a5741d'),
                new InterdimensionalSiblings(
                    new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray([
                            'language' => 'pl'
                        ]),
                        NodeAggregateId::fromString('7db24575-1227-4c7c-87ff-7aaa98532a94')
                    )
                )
            )
        ];

        yield 'singleNodeMoveMappingWithParent' => [
            'payload' => [
                 'contentStreamId' => '516b9125-ccd4-4f73-ba49-05cb2c04d9f1',
                 'nodeAggregateId' => 'b894d0d3-f941-415b-ad1e-33054582bf00',
                 'nodeMoveMappings' => [
                     [
                         'movedNodeOrigin' => [
                             'language' => 'pl'
                         ],
                         'newLocations' => [
                             [
                                 'coveredDimensionSpacePoint' => [
                                     'language' => 'pl'
                                 ],
                                 'newParent' => [
                                     'nodeAggregateId' => '46e2139a-32e3-457d-bc1e-4e583c5c5530',
                                     'originDimensionSpacePoint' => [
                                         'language' => 'pl'
                                     ],
                                 ]
                             ]
                         ]
                     ],
                 ],
                'workspaceName' => 'user-soee'
            ],
            'expectedEvent' => new NodeAggregateWasMoved(
                WorkspaceName::fromString('user-soee'),
                ContentStreamId::fromString('516b9125-ccd4-4f73-ba49-05cb2c04d9f1'),
                NodeAggregateId::fromString('b894d0d3-f941-415b-ad1e-33054582bf00'),
                NodeAggregateId::fromString('46e2139a-32e3-457d-bc1e-4e583c5c5530'),
                new InterdimensionalSiblings(
                    new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray([
                            'language' => 'pl'
                        ]),
                        null
                    )
                )
            )
        ];


        yield 'mulitpleNodeMoveMappingWithSibling' => [
            'payload' => [
                'contentStreamId' => 'd742c335-b733-47d6-aebe-c809a3afc972',
                'nodeAggregateId' => 'eebf6ea3-a69b-48c0-af16-d0e14c0bb646',
                'nodeMoveMappings' => [
                    [
                        'movedNodeOrigin' => [
                            'language' => 'en'
                        ],
                        'newLocations' => [
                            [
                                'coveredDimensionSpacePoint' => [
                                    'language' => 'en'
                                ],
                                'newSucceedingSibling' => [
                                    'nodeAggregateId' => 'eebf6ea3-a69b-48c0-af16-d0e14c0bb646',
                                    'originDimensionSpacePoint' => [
                                        'language' => 'en'
                                    ],
                                    'parentNodeAggregateId' => '6b6e1251-4346-494f-ac56-526a30a5741d',
                                    'parentOriginDimensionSpacePoint' => [
                                        'language' => 'en'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'movedNodeOrigin' => [
                            'language' => 'pl'
                        ],
                        'newLocations' => [
                            [
                                'coveredDimensionSpacePoint' => [
                                    'language' => 'pl'
                                ],
                                'newSucceedingSibling' => [
                                    'nodeAggregateId' => '37342037-b69b-476b-bf7b-9383f33efa1b',
                                    'originDimensionSpacePoint' => [
                                        'language' => 'pl'
                                    ],
                                    'parentNodeAggregateId' => '6b6e1251-4346-494f-ac56-526a30a5741d',
                                    'parentOriginDimensionSpacePoint' => [
                                        'language' => 'pl'
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
                'workspaceName' => 'user-soee'
            ],
            'expectedEvent' => new NodeAggregateWasMoved(
                WorkspaceName::fromString('user-soee'),
                ContentStreamId::fromString('d742c335-b733-47d6-aebe-c809a3afc972'),
                NodeAggregateId::fromString('eebf6ea3-a69b-48c0-af16-d0e14c0bb646'),
                NodeAggregateId::fromString('6b6e1251-4346-494f-ac56-526a30a5741d'),
                new InterdimensionalSiblings(
                    new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray([
                            'language' => 'en'
                        ]),
                        NodeAggregateId::fromString('eebf6ea3-a69b-48c0-af16-d0e14c0bb646')
                    ),
                    new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray([
                            'language' => 'pl'
                        ]),
                        NodeAggregateId::fromString('37342037-b69b-476b-bf7b-9383f33efa1b')
                    )
                )
            )
        ];
    }
}
