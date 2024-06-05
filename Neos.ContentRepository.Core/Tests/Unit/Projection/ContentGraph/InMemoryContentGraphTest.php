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

namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph;

use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionConstraintSet;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValue;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValues;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueSpecializationDepth;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueVariationEdge;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueVariationEdges;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\DimensionSpacePointsBySubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class InMemoryContentGraphTest extends TestCase
{
    /**
     * @dataProvider contentGraphProvider
     */
    public function testToMinimalConstitutingEvents(
        InMemoryContentGraph $contentGraph,
        InterDimensionalVariationGraph $variationGraph,
        Events $expectedEvents
    ) {
        self::assertEquals($expectedEvents, $contentGraph->toMinimalConstitutingEvents($variationGraph));
    }

    /**
     * @return \Traversable<string,array<string,mixed>>
     */
    public static function contentGraphProvider(): \Traversable
    {
        $contentRepositoryId = ContentRepositoryId::fromString('default');
        $workspaceName = WorkspaceName::forLive();
        $contentStreamId = ContentStreamId::fromString('cs-id');
        $generalDsp = DimensionSpacePoint::fromArray(['example' => 'general']);
        $sourceDsp = DimensionSpacePoint::fromArray(['example' => 'source']);
        $specDsp = DimensionSpacePoint::fromArray(['example' => 'spec']);
        $peerDsp = DimensionSpacePoint::fromArray(['example' => 'peer']);
        $coveredDimensionSpacePoints = DimensionSpacePointSet::fromArray([
            $generalDsp,
            $sourceDsp,
            $specDsp,
            $peerDsp,
        ]);

        $contentDimensionSource = new class implements ContentDimensionSourceInterface {
            private ContentDimension $contentDimension;
            public function __construct()
            {
                $generalValue =  new ContentDimensionValue(
                    'general',
                    new ContentDimensionValueSpecializationDepth(0),
                );
                $sourceValue =  new ContentDimensionValue(
                    'source',
                    new ContentDimensionValueSpecializationDepth(1),
                );
                $specValue =  new ContentDimensionValue(
                    'spec',
                    new ContentDimensionValueSpecializationDepth(2),
                );
                $peerValue =  new ContentDimensionValue(
                    'peer',
                    new ContentDimensionValueSpecializationDepth(0),
                );

                $this->contentDimension = new ContentDimension(
                    new ContentDimensionId('example'),
                    new ContentDimensionValues([
                        $generalValue,
                        $sourceValue,
                        $specValue,
                        $peerValue,
                    ]),
                    new ContentDimensionValueVariationEdges(
                        new ContentDimensionValueVariationEdge(
                            $sourceValue,
                            $generalValue,
                        ),
                        new ContentDimensionValueVariationEdge(
                            $specValue,
                            $sourceValue,
                        )
                    )
                );
            }

            public function getDimension(ContentDimensionId $dimensionId): ?ContentDimension
            {
                return $dimensionId->equals($this->contentDimension->id)
                    ? $this->contentDimension
                    : null;
            }

            /**
             * @inheritDoc
             */
            public function getContentDimensionsOrderedByPriority(): array
            {
                return [$this->contentDimension->id->value => $this->contentDimension];
            }
        };
        $variationGraph = new InterDimensionalVariationGraph(
            $contentDimensionSource,
            new ContentDimensionZookeeper($contentDimensionSource)
        );

        $rootford = self::createNode(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            originDimensionSpacePoint: OriginDimensionSpacePoint::createWithoutDimensions(),
            aggregateId: NodeAggregateId::fromString('lady-eleonode-rootford'),
            classification: NodeAggregateClassification::CLASSIFICATION_ROOT,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:Root'),
            contentStreamId: $contentStreamId,
        );
        $anotherRootford = self::createNode(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            originDimensionSpacePoint: OriginDimensionSpacePoint::createWithoutDimensions(),
            aggregateId: NodeAggregateId::fromString('another-rootford'),
            classification: NodeAggregateClassification::CLASSIFICATION_ROOT,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:AnotherRoot'),
            contentStreamId: $contentStreamId,
        );
        $rootfordAggregate = self::createRootNodeAggregate(
            NodeAggregateId::fromString('lady-eleonode-rootford'),
            NodeTypeName::fromString('Neos.ContentRepository:Root'),
            $workspaceName,
            $contentStreamId,
            $coveredDimensionSpacePoints,
            $rootford,
        );

        yield 'onlyRootNodes' => [
            'contentGraph' => new InMemoryContentGraph(
                $workspaceName,
                $contentStreamId,
                NodeAggregates::fromArray([
                    $rootfordAggregate,
                    self::createRootNodeAggregate(
                        NodeAggregateId::fromString('another-rootford'),
                        NodeTypeName::fromString('Neos.ContentRepository:AnotherRoot'),
                        $workspaceName,
                        $contentStreamId,
                        $coveredDimensionSpacePoints,
                        $anotherRootford
                    ),
                ]),
                [],
                [
                    $generalDsp->hash => [
                        $rootfordAggregate->nodeAggregateId->value => Nodes::createEmpty(),
                        'another-rootford' => Nodes::createEmpty(),
                    ],
                    $sourceDsp->hash => [
                        $rootfordAggregate->nodeAggregateId->value => Nodes::createEmpty(),
                        'another-rootford' => Nodes::createEmpty(),
                    ],
                    $specDsp->hash => [
                        $rootfordAggregate->nodeAggregateId->value => Nodes::createEmpty(),
                        'another-rootford' => Nodes::createEmpty(),
                    ],
                    $peerDsp->hash => [
                        $rootfordAggregate->nodeAggregateId->value => Nodes::createEmpty(),
                        'another-rootford' => Nodes::createEmpty(),
                    ],
                ],
            ),
            'variationGraph' => $variationGraph,
            'expectedEvents' => Events::fromArray([
                new RootNodeAggregateWithNodeWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    NodeAggregateId::fromString('lady-eleonode-rootford'),
                    NodeTypeName::fromString('Neos.ContentRepository:Root'),
                    $coveredDimensionSpacePoints,
                    NodeAggregateClassification::CLASSIFICATION_ROOT,
                ),
                new RootNodeAggregateWithNodeWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    NodeAggregateId::fromString('another-rootford'),
                    NodeTypeName::fromString('Neos.ContentRepository:AnotherRoot'),
                    $coveredDimensionSpacePoints,
                    NodeAggregateClassification::CLASSIFICATION_ROOT,
                ),
            ]),
        ];

        $generalNodenborough = self::createNode(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            originDimensionSpacePoint: OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
            aggregateId: NodeAggregateId::fromString('sir-david-nodenborough'),
            classification: NodeAggregateClassification::CLASSIFICATION_REGULAR,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:Testing.Document'),
            contentStreamId: $contentStreamId,
            serializedPropertyValues: SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'general text'
                ]
            ]),
        );
        $generalNodington = self::createNode(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            originDimensionSpacePoint: OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
            aggregateId: NodeAggregateId::fromString('sir-nodeward-nodington-iii'),
            classification: NodeAggregateClassification::CLASSIFICATION_REGULAR,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:Testing.Document'),
            contentStreamId: $contentStreamId,
            serializedPropertyValues: SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'general sibling text'
                ]
            ]),
        );
        $generalTetherton = self::createNode(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            originDimensionSpacePoint: OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
            aggregateId: NodeAggregateId::fromString('nodewyn-tetherton'),
            classification: NodeAggregateClassification::CLASSIFICATION_TETHERED,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:Testing.Tethered'),
            contentStreamId: $contentStreamId,
            serializedPropertyValues: SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'general tethered text'
                ]
            ]),
            name: NodeName::fromString('tethered'),
        );
        $sourceNodenborough = self::createVariant(
            $generalNodenborough,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
            SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'source text'
                ]
            ]),
        );
        $sourceNodington = self::createVariant(
            $generalNodington,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
            SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'source sibling text'
                ]
            ]),
        );
        $sourceTetherton = self::createVariant(
            $generalTetherton,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
            SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'source tethered text'
                ]
            ]),
        );
        $specialNodenborough = self::createVirtualVariant($sourceNodenborough, $specDsp);
        $specialNodington = self::createVirtualVariant($sourceNodington, $specDsp);
        $specialTetherton = self::createVirtualVariant($sourceTetherton, $specDsp);
        $peerNodenborough = self::createVariant(
            $generalNodenborough,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
            SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'peer text'
                ]
            ]),
        );
        $peerNodington = self::createVariant(
            $generalNodington,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
            SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'peer sibling text'
                ]
            ]),
        );
        $peerTetherton = self::createVariant(
            $generalTetherton,
            OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
            SerializedPropertyValues::fromArray([
                'text' => [
                    'type' => 'string',
                    'value' => 'peer tethered text'
                ]
            ]),
        );

        yield 'nodesWithVariants' => [
            'contentGraph' => new InMemoryContentGraph(
                $workspaceName,
                $contentStreamId,
                NodeAggregates::fromArray([$rootfordAggregate]),
                [
                    $generalDsp->hash => [
                        $generalNodenborough->aggregateId->value => $rootford,
                        $generalNodington->aggregateId->value => $rootford,
                        $generalTetherton->aggregateId->value => $generalNodenborough,
                    ],
                    $sourceDsp->hash => [
                        $sourceNodenborough->aggregateId->value => $rootford,
                        $sourceNodington->aggregateId->value => $rootford,
                        $sourceTetherton->aggregateId->value => $sourceNodenborough,
                    ],
                    $specDsp->hash => [
                        $specialNodenborough->aggregateId->value => $rootford,
                        $specialNodington->aggregateId->value => $rootford,
                        $specialTetherton->aggregateId->value => $specialNodenborough,
                    ],
                    $peerDsp->hash => [
                        $peerNodenborough->aggregateId->value => $rootford,
                        $peerNodington->aggregateId->value => $rootford,
                        $peerTetherton->aggregateId->value => $peerNodenborough,
                    ]
                ],
                [
                    $generalDsp->hash => [
                        $rootford->aggregateId->value => Nodes::fromArray([$generalNodenborough, $generalNodington]),
                        $generalNodenborough->aggregateId->value => Nodes::fromArray([$generalTetherton]),
                        $generalNodington->aggregateId->value => Nodes::fromArray([]),
                        $generalTetherton->aggregateId->value => Nodes::fromArray([]),
                    ],
                    $sourceDsp->hash => [
                        $rootford->aggregateId->value => Nodes::fromArray([$sourceNodenborough, $sourceNodington]),
                        $sourceNodenborough->aggregateId->value => Nodes::fromArray([$sourceTetherton]),
                        $sourceNodington->aggregateId->value => Nodes::fromArray([]),
                        $sourceTetherton->aggregateId->value => Nodes::fromArray([]),
                    ],
                    $specDsp->hash => [
                        $rootford->aggregateId->value => Nodes::fromArray([$specialNodenborough, $specialNodington]),
                        $specialNodenborough->aggregateId->value => Nodes::fromArray([$specialTetherton]),
                        $specialNodington->aggregateId->value => Nodes::fromArray([]),
                        $specialTetherton->aggregateId->value => Nodes::fromArray([]),
                    ],
                    $peerDsp->hash => [
                        $rootford->aggregateId->value => Nodes::fromArray([$peerNodenborough, $peerNodington]),
                        $peerNodenborough->aggregateId->value => Nodes::fromArray([$peerTetherton]),
                        $peerNodington->aggregateId->value => Nodes::fromArray([]),
                        $peerTetherton->aggregateId->value => Nodes::fromArray([]),
                    ],
                ],
            ),
            'variationGraph' => $variationGraph,
            'expectedEvents' => Events::fromArray([
                new RootNodeAggregateWithNodeWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $rootfordAggregate->nodeAggregateId,
                    $rootfordAggregate->nodeTypeName,
                    $coveredDimensionSpacePoints,
                    NodeAggregateClassification::CLASSIFICATION_ROOT,
                ),
                new NodeAggregateWithNodeWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalNodenborough->aggregateId,
                    $generalNodenborough->nodeTypeName,
                    $generalNodenborough->originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$generalDsp, $sourceDsp, $specDsp])
                    ),
                    $rootfordAggregate->nodeAggregateId,
                    null,
                    SerializedPropertyValues::fromArray([
                        'text' => [
                            'type' => 'string',
                            'value' => 'general text'
                        ]
                    ]),
                    NodeAggregateClassification::CLASSIFICATION_REGULAR,
                ),
                new NodeAggregateWithNodeWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalTetherton->aggregateId,
                    $generalTetherton->nodeTypeName,
                    $generalTetherton->originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$generalDsp, $sourceDsp, $specDsp])
                    ),
                    $generalNodenborough->aggregateId,
                    NodeName::fromString('tethered'),
                    SerializedPropertyValues::fromArray([
                        'text' => [
                            'type' => 'string',
                            'value' => 'general tethered text'
                        ]
                    ]),
                    NodeAggregateClassification::CLASSIFICATION_TETHERED,
                ),
                new NodeAggregateWithNodeWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalNodington->aggregateId,
                    $generalNodington->nodeTypeName,
                    $generalNodington->originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$generalDsp, $sourceDsp, $specDsp])
                    ),
                    $rootfordAggregate->nodeAggregateId,
                    null,
                    SerializedPropertyValues::fromArray([
                        'text' => [
                            'type' => 'string',
                            'value' => 'general sibling text'
                        ]
                    ]),
                    NodeAggregateClassification::CLASSIFICATION_REGULAR,
                ),
                new NodeSpecializationVariantWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalNodenborough->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$sourceDsp, $specDsp])
                    ),
                ),
                new NodePropertiesWereSet(
                    $workspaceName,
                    $contentStreamId,
                    $sourceNodenborough->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
                    DimensionSpacePointSet::fromArray([$sourceDsp, $specDsp]),
                    $sourceNodenborough->properties->serialized(),
                    PropertyNames::createEmpty(),
                ),
                new NodeSpecializationVariantWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalTetherton->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$sourceDsp, $specDsp])
                    ),
                ),
                new NodePropertiesWereSet(
                    $workspaceName,
                    $contentStreamId,
                    $sourceTetherton->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
                    DimensionSpacePointSet::fromArray([$sourceDsp, $specDsp]),
                    $sourceTetherton->properties->serialized(),
                    PropertyNames::createEmpty(),
                ),
                new NodeSpecializationVariantWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalNodington->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$sourceDsp, $specDsp])
                    ),
                ),
                new NodePropertiesWereSet(
                    $workspaceName,
                    $contentStreamId,
                    $sourceNodington->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($sourceDsp),
                    DimensionSpacePointSet::fromArray([$sourceDsp, $specDsp]),
                    $sourceNodington->properties->serialized(),
                    PropertyNames::createEmpty(),
                ),
                new NodePeerVariantWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalNodenborough->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$peerDsp])
                    ),
                ),
                new NodePropertiesWereSet(
                    $workspaceName,
                    $contentStreamId,
                    $peerNodenborough->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
                    DimensionSpacePointSet::fromArray([$peerDsp]),
                    $peerNodenborough->properties->serialized(),
                    PropertyNames::createEmpty(),
                ),
                new NodePeerVariantWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalTetherton->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$peerDsp])
                    ),
                ),
                new NodePropertiesWereSet(
                    $workspaceName,
                    $contentStreamId,
                    $peerTetherton->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
                    DimensionSpacePointSet::fromArray([$peerDsp]),
                    $peerTetherton->properties->serialized(),
                    PropertyNames::createEmpty(),
                ),
                new NodePeerVariantWasCreated(
                    $workspaceName,
                    $contentStreamId,
                    $generalNodington->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($generalDsp),
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        DimensionSpacePointSet::fromArray([$peerDsp])
                    ),
                ),
                new NodePropertiesWereSet(
                    $workspaceName,
                    $contentStreamId,
                    $peerNodington->aggregateId,
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($peerDsp),
                    DimensionSpacePointSet::fromArray([$peerDsp]),
                    $peerNodington->properties->serialized(),
                    PropertyNames::createEmpty(),
                ),
            ]),
        ];
    }

    private static function createNode(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $aggregateId,
        NodeAggregateClassification $classification,
        NodeTypeName $nodeTypeName,
        ContentStreamId $contentStreamId,
        ?SerializedPropertyValues $serializedPropertyValues = null,
        ?NodeName $name = null,
    ): Node {
        $now = new \DateTimeImmutable();

        return Node::create(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            dimensionSpacePoint: $originDimensionSpacePoint->toDimensionSpacePoint(),
            aggregateId: $aggregateId,
            originDimensionSpacePoint: $originDimensionSpacePoint,
            classification: $classification,
            nodeTypeName: $nodeTypeName,
            properties: new PropertyCollection(
                $serializedPropertyValues ?: SerializedPropertyValues::createEmpty(),
                new PropertyConverter(new Serializer())
            ),
            nodeName: $name,
            tags: NodeTags::createEmpty(),
            timestamps: Timestamps::create($now, $now, null, null),
            visibilityConstraints: VisibilityConstraints::withoutRestrictions(),
            nodeType: null,
            contentStreamId: $contentStreamId,
        );
    }

    private static function createVariant(
        Node $node,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ?SerializedPropertyValues $serializedPropertyValues = null
    ): Node {
        return Node::create(
            $node->contentRepositoryId,
            $node->workspaceName,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            $node->aggregateId,
            $originDimensionSpacePoint,
            $node->classification,
            $node->nodeTypeName,
            $serializedPropertyValues
                ? new PropertyCollection($serializedPropertyValues, new PropertyConverter(new Serializer()))
                : $node->properties,
            $node->name,
            $node->tags,
            $node->timestamps,
            $node->visibilityConstraints,
            $node->nodeType,
            $node->subgraphIdentity->contentStreamId
        );
    }

    private static function createVirtualVariant(Node $node, DimensionSpacePoint $dimensionSpacePoint): Node
    {
        return Node::create(
            $node->contentRepositoryId,
            $node->workspaceName,
            $dimensionSpacePoint,
            $node->aggregateId,
            $node->originDimensionSpacePoint,
            $node->classification,
            $node->nodeTypeName,
            $node->properties,
            $node->name,
            $node->tags,
            $node->timestamps,
            $node->visibilityConstraints,
            $node->nodeType,
            $node->subgraphIdentity->contentStreamId
        );
    }

    private static function createRootNodeAggregate(
        NodeAggregateId $aggregateId,
        NodeTypeName $nodeTypeName,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        Node $rootNode,
    ): NodeAggregate {
        $contentRepositoryId = ContentRepositoryId::fromString('default');
        $emptyOrigin = OriginDimensionSpacePoint::createWithoutDimensions();

        $nodesByCoveredDimensionSpacePoint = [];
        $occupationByCovered = [];
        $coverageByOccupant = [];
        foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash] = $rootNode;
            $occupationByCovered[$coveredDimensionSpacePoint->hash] = $emptyOrigin;
            $coverageByOccupant[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
        }

        return NodeAggregate::create(
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            nodeAggregateId: $aggregateId,
            classification: NodeAggregateClassification::CLASSIFICATION_ROOT,
            nodeTypeName: $nodeTypeName,
            nodeName: null,
            occupiedDimensionSpacePoints: new OriginDimensionSpacePointSet([$emptyOrigin]),
            nodesByOccupiedDimensionSpacePoint: [
                $emptyOrigin->hash => $rootNode,
            ],
            coverageByOccupant: CoverageByOrigin::fromArray([
                $emptyOrigin->hash => $coverageByOccupant
            ]),
            coveredDimensionSpacePoints: $coveredDimensionSpacePoints,
            nodesByCoveredDimensionSpacePoint: $nodesByCoveredDimensionSpacePoint,
            occupationByCovered: OriginByCoverage::fromArray($occupationByCovered),
            dimensionSpacePointsBySubtreeTags: new DimensionSpacePointsBySubtreeTags([]),
            contentStreamId: $contentStreamId
        );
    }
}
