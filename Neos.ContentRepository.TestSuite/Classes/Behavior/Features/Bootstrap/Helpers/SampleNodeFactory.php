<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Symfony\Component\Serializer\Serializer;

class SampleNodeFactory
{
    public static function createSampleNode(): Node
    {
        return Node::create(
            contentRepositoryId: ContentRepositoryId::fromString('default'),
            workspaceName: WorkspaceName::forLive(),
            dimensionSpacePoint: DimensionSpacePoint::createWithoutDimensions(),
            aggregateId: NodeAggregateId::fromString('nody-mc-nodeface'),
            originDimensionSpacePoint: OriginDimensionSpacePoint::createWithoutDimensions(),
            classification: NodeAggregateClassification::CLASSIFICATION_REGULAR,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:Testing'),
            properties: new PropertyCollection(
                SerializedPropertyValues::fromArray([
                    'text' => SerializedPropertyValue::create(
                        value: 'textValue',
                        type: 'string',
                    ),
                    'otherText' => SerializedPropertyValue::create(
                        value: 'otherTextValue',
                        type: 'string',
                    ),
                    'textToRemove' => SerializedPropertyValue::create(
                        value: 'textToRemoveValue',
                        type: 'string',
                    ),
                ]),
                new PropertyConverter(new Serializer()),
            ),
            name: NodeName::fromString('node'),
            tags: NodeTags::create(
                SubtreeTags::create(
                    SubtreeTag::fromString('my-tag'),
                ),
                SubtreeTags::create(
                    SubtreeTag::fromString('someone-elses-tag'),
                )
            ),
            timestamps: Timestamps::create(
                created: self::createUTCDate('2026-05-12 13:58:25'),
                originalCreated: self::createUTCDate('2026-05-12 13:58:25'),
                lastModified: null,
                originalLastModified: null,
            ),
            visibilityConstraints: VisibilityConstraints::createEmpty(),
        );
    }

    public static function modifyNodeWith(
        Node $node,
        ?ContentRepositoryId $contentRepositoryId = null,
        ?WorkspaceName $workspaceName = null,
        ?DimensionSpacePoint $dimensionSpacePoint = null,
        ?NodeAggregateId $aggregateId = null,
        ?OriginDimensionSpacePoint $originDimensionSpacePoint = null,
        ?NodeAggregateClassification $classification = null,
        ?NodeTypeName $nodeTypeName = null,
        ?SerializedPropertyValues $properties = null,
        ?NodeName $name = null,
        bool $unsetNodeName = false,
        ?NodeTags $tags = null,
        ?Timestamps $timestamps = null,
    ): Node {
        return Node::create(
            contentRepositoryId: $contentRepositoryId ?: $node->contentRepositoryId,
            workspaceName: $workspaceName ?: $node->workspaceName,
            dimensionSpacePoint: $dimensionSpacePoint ?: $node->dimensionSpacePoint,
            aggregateId: $aggregateId ?: $node->aggregateId,
            originDimensionSpacePoint: $originDimensionSpacePoint ?: $node->originDimensionSpacePoint,
            classification: $classification ?: $node->classification,
            nodeTypeName: $nodeTypeName ?: $node->nodeTypeName,
            properties: $properties
                ? new PropertyCollection(
                    serializedPropertyValues: $properties,
                    propertyConverter: new PropertyConverter(new Serializer()),
                )
                : $node->properties,
            name: $unsetNodeName ? null : ($name ?: $node->name),
            tags: $tags ?: $node->tags,
            timestamps: $timestamps ?: $node->timestamps,
            visibilityConstraints: $node->visibilityConstraints,
        );
    }

    public static function createSampleReference(): Reference
    {
        return new Reference(
            node: self::createSampleNode(),
            name: ReferenceName::fromString('my-reference'),
            properties: new PropertyCollection(
                SerializedPropertyValues::fromArray([
                    'text' => SerializedPropertyValue::create(
                        value: 'textValue',
                        type: 'string',
                    ),
                    'otherText' => SerializedPropertyValue::create(
                        value: 'otherTextValue',
                        type: 'string',
                    ),
                    'textToRemove' => SerializedPropertyValue::create(
                        value: 'textToRemoveValue',
                        type: 'string',
                    ),
                ]),
                new PropertyConverter(new Serializer()),
            ),
        );
    }

    public static function modifyReferenceWith(
        Reference $reference,
        ?Node $node = null,
        ?ReferenceName $name = null,
        ?SerializedPropertyValues $properties = null,
        bool $unsetProperties = false,
    ): Reference {
        return new Reference(
            node: $node ?: $reference->node,
            name: $name ?: $reference->name,
            properties: $unsetProperties
                ? null
                : (
                    $properties
                        ? new PropertyCollection(
                            serializedPropertyValues: $properties,
                            propertyConverter: new PropertyConverter(new Serializer()),
                        )
                        : $reference->properties
                ),
        );
    }

    private static function createUTCDate(string $dateString): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat(
            format: 'Y-m-d H:i:s',
            datetime: $dateString,
            timezone: new \DateTimeZone('UTC'),
        );

        return $date ?: throw new \RuntimeException('Invalid date ' . $dateString);
    }
}
