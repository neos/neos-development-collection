<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Tests\Unit;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\NodeDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\PropertyDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\SampleNodeFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class NodeDiffTest extends TestCase
{
    /**
     * @dataProvider nodeComparisonProvider
     */
    public function testFromNodeComparison(Node $referenceNode, Node $nodeToCompare, ?NodeDiff $expectedNodeDiff): void
    {
        Assert::assertEquals(
            $expectedNodeDiff,
            NodeDiff::tryFromNodeComparison($nodeToCompare, $referenceNode, null)
        );
    }

    public static function nodeComparisonProvider(): iterable
    {
        $referenceNode = SampleNodeFactory::createSampleNode();

        yield 'identicalNodes' => [
            'referenceNode' => $referenceNode,
            'nodeToCompare' => $referenceNode,
            'expectedNodeDiff' => null,
        ];

        yield 'nodeWithRemovedName' => [
            'referenceNode' => $referenceNode,
            'nodeToCompare' => SampleNodeFactory::modifyNodeWith(
                node: $referenceNode,
                unsetNodeName: true,
            ),
            'expectedNodeDiff' => NodeDiff::tryCreate(
                discriminator: $referenceNode->aggregateId,
                nameWasUnset: true,
            )
        ];

        yield 'differingNode' => [
            'referenceNode' => $referenceNode,
            'nodeToCompare' => SampleNodeFactory::modifyNodeWith(
                node: $referenceNode,
                contentRepositoryId: ContentRepositoryId::fromString('other'),
                workspaceName: WorkspaceName::fromString('other'),
                dimensionSpacePoint: DimensionSpacePoint::fromArray(['example' => 'value']),
                aggregateId: NodeAggregateId::fromString('other'),
                originDimensionSpacePoint: OriginDimensionSpacePoint::fromArray(['example' => 'value']),
                classification: NodeAggregateClassification::CLASSIFICATION_TETHERED,
                nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:OtherTesting'),
            ),
            'expectedNodeDiff' => NodeDiff::tryCreate(
                discriminator: NodeAggregateId::fromString('other'),
                contentRepositoryId: ContentRepositoryId::fromString('other'),
                workspaceName: WorkspaceName::fromString('other'),
                dimensionSpacePoint: DimensionSpacePoint::fromArray(['example' => 'value']),
                aggregateId: NodeAggregateId::fromString('other'),
                originDimensionSpacePoint: OriginDimensionSpacePoint::fromArray(['example' => 'value']),
                classification: NodeAggregateClassification::CLASSIFICATION_TETHERED,
                nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:OtherTesting'),
            )
        ];

        yield 'otherDifferingNode' => [
            'referenceNode' => $referenceNode,
            'nodeToCompare' => SampleNodeFactory::modifyNodeWith(
                node: $referenceNode,
                properties: SerializedPropertyValues::fromArray([
                    'text' => SerializedPropertyValue::create(
                        value: 'modifiedTextValue',
                        type: 'string',
                    ),
                    'otherText' => SerializedPropertyValue::create(
                        value: 'otherTextValue',
                        type: 'string',
                    ),
                    'additionalText' => SerializedPropertyValue::create(
                        value: 'additionalTextValue',
                        type: 'string',
                    ),
                ]),
                name: NodeName::fromString('other'),
                tags: NodeTags::create(
                    SubtreeTags::create(
                        SubtreeTag::fromString('my-other-tag'),
                    ),
                    SubtreeTags::create(
                        SubtreeTag::fromString('someone-elses-other-tag'),
                    )
                ),
                timestamps: Timestamps::create(
                    created: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                    originalCreated: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                    lastModified: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                    originalLastModified: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                ),
            ),
            'expectedNodeDiff' => NodeDiff::tryCreate(
                discriminator: $referenceNode->aggregateId,
                 properties: PropertyDiff::tryCreate(
                     addedProperties: SerializedPropertyValues::fromArray([
                        'additionalText' => SerializedPropertyValue::create(
                            value: 'additionalTextValue',
                            type: 'string',
                        ),
                     ]),
                     modifiedProperties: SerializedPropertyValues::fromArray([
                        'text' => SerializedPropertyValue::create(
                            value: 'modifiedTextValue',
                            type: 'string',
                        ),
                     ]),
                     removedProperties: PropertyNames::fromArray(['textToRemove']),
                ),
                name: NodeName::fromString('other'),
                tags: NodeTags::create(
                    SubtreeTags::create(
                        SubtreeTag::fromString('my-other-tag'),
                    ),
                    SubtreeTags::create(
                        SubtreeTag::fromString('someone-elses-other-tag'),
                    )
                ),
                timestamps: Timestamps::create(
                    created: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                    originalCreated: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                    lastModified: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                    originalLastModified: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2026-05-12 15:02:21')
                        ->setTimezone(new \DateTimeZone('UTC')),
                ),
            ),
        ];
    }
}
