<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The difference between two node read models
 */
#[Flow\Proxy(false)]
final readonly class NodeDiff implements \JsonSerializable
{
    private function __construct(
        public NodeAggregateId $discriminator,
        public ?ContentRepositoryId $contentRepositoryId,
        public ?WorkspaceName $workspaceName,
        public ?DimensionSpacePoint $dimensionSpacePoint,
        public ?NodeAggregateId $aggregateId,
        public ?OriginDimensionSpacePoint $originDimensionSpacePoint,
        public ?NodeAggregateClassification $classification,
        public ?NodeTypeName $nodeTypeName,
        public ?PropertyDiff $properties,
        public ?NodeName $name,
        public bool $nameWasUnset,
        public ?NodeTags $tags,
        public ?Timestamps $timestamps,
    ) {
    }

    public static function tryCreate(
        NodeAggregateId $discriminator,
        ?ContentRepositoryId $contentRepositoryId = null,
        ?WorkspaceName $workspaceName = null,
        ?DimensionSpacePoint $dimensionSpacePoint = null,
        ?NodeAggregateId $aggregateId = null,
        ?OriginDimensionSpacePoint $originDimensionSpacePoint = null,
        ?NodeAggregateClassification $classification = null,
        ?NodeTypeName $nodeTypeName = null,
        ?PropertyDiff $properties = null,
        ?NodeName $name = null,
        bool $nameWasUnset = false,
        ?NodeTags $tags = null,
        ?Timestamps $timestamps = null,
    ): ?self {
        if (
            $contentRepositoryId === null
            && $workspaceName === null
            && $dimensionSpacePoint === null
            && $aggregateId === null
            && $originDimensionSpacePoint === null
            && $classification === null
            && $nodeTypeName === null
            && $properties === null
            && $name === null
            && $nameWasUnset === false
            && $tags === null
            && $timestamps === null
        ) {
            return null;
        }

        return new self(
            discriminator: $discriminator,
            contentRepositoryId: $contentRepositoryId,
            workspaceName: $workspaceName,
            dimensionSpacePoint: $dimensionSpacePoint,
            aggregateId: $aggregateId,
            originDimensionSpacePoint: $originDimensionSpacePoint,
            classification: $classification,
            nodeTypeName: $nodeTypeName,
            properties: $properties,
            name: $name,
            nameWasUnset: $nameWasUnset,
            tags: $tags,
            timestamps: $timestamps,
        );
    }

    public static function tryFromNodeComparison(Node $nodeToCompare, Node $referenceNode, ?WorkspaceName $expectedWorkspaceName): ?self
    {
        $differentContentRepositoryId = $nodeToCompare->contentRepositoryId->equals($referenceNode->contentRepositoryId)
            ? null
            : $nodeToCompare->contentRepositoryId;
        $differentWorkspaceName = $nodeToCompare->workspaceName->equals($expectedWorkspaceName ?: $referenceNode->workspaceName)
            ? null
            : $nodeToCompare->workspaceName;
        $differentDimensionSpacePoint = $nodeToCompare->dimensionSpacePoint->equals($referenceNode->dimensionSpacePoint)
            ? null
            : $nodeToCompare->dimensionSpacePoint;
        $differentAggregateId = $nodeToCompare->aggregateId->equals($referenceNode->aggregateId)
            ? null
            : $nodeToCompare->aggregateId;
        $differentOriginDimensionSpacePoint = $nodeToCompare->originDimensionSpacePoint->equals($referenceNode->originDimensionSpacePoint)
            ? null
            : $nodeToCompare->originDimensionSpacePoint;
        $differentClassification = $nodeToCompare->classification->equals($referenceNode->classification)
            ? null
            : $nodeToCompare->classification;
        $differentNodeTypeName = $nodeToCompare->nodeTypeName->equals($referenceNode->nodeTypeName)
            ? null
            : $nodeToCompare->nodeTypeName;
        $differentProperties = PropertyDiff::tryFromNodeComparison(
            $nodeToCompare->properties->serialized(),
            $referenceNode->properties->serialized(),
        );
        if ($nodeToCompare->name) {
            $nameWasUnset = false;
            if ($referenceNode->name) {
                $differentName = $nodeToCompare->name->equals($referenceNode->name)
                    ? null
                    : $nodeToCompare->name;
            } else {
                $differentName = $nodeToCompare->name;
            }
        } else {
            $nameWasUnset = $referenceNode->name instanceof NodeName;
            $differentName = null;
        }

        $differentTags = $nodeToCompare->tags->equals($referenceNode->tags)
            ? null
            : $nodeToCompare->tags;

        $differentTimestamps = $nodeToCompare->timestamps->equals($referenceNode->timestamps)
            ? null
            : $nodeToCompare->timestamps;

        return self::tryCreate(
            discriminator: $nodeToCompare->aggregateId,
            contentRepositoryId: $differentContentRepositoryId,
            workspaceName: $differentWorkspaceName,
            dimensionSpacePoint: $differentDimensionSpacePoint,
            aggregateId: $differentAggregateId,
            originDimensionSpacePoint: $differentOriginDimensionSpacePoint,
            classification: $differentClassification,
            nodeTypeName: $differentNodeTypeName,
            properties: $differentProperties,
            name: $differentName,
            nameWasUnset: $nameWasUnset,
            tags: $differentTags,
            timestamps: $differentTimestamps,
        );
    }

    public static function forAnAdditionalNode(Node $node): self
    {
        return new self(
            discriminator: $node->aggregateId,
            contentRepositoryId: $node->contentRepositoryId,
            workspaceName: $node->workspaceName,
            dimensionSpacePoint: $node->dimensionSpacePoint,
            aggregateId: $node->aggregateId,
            originDimensionSpacePoint: $node->originDimensionSpacePoint,
            classification: $node->classification,
            nodeTypeName: $node->nodeTypeName,
            properties: PropertyDiff::tryForAnAdditionalNode($node->properties->serialized()),
            name: $node->name,
            nameWasUnset: false,
            tags: $node->tags->isEmpty() ? null : $node->tags,
            timestamps: $node->timestamps,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                '_discriminator' => $this->discriminator,
                'contentRepositoryId' => $this->contentRepositoryId,
                'workspaceName' => $this->workspaceName,
                'dimensionSpacePoint' => $this->dimensionSpacePoint,
                'aggregateId' => $this->aggregateId,
                'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
                'classification' => $this->classification,
                'nodeTypeName' => $this->nodeTypeName,
                'properties' => $this->properties,
                'name' => $this->name,
                'nameWasRemoved' => $this->nameWasUnset,
                'tags' => $this->tags,
                'timestamps' => $this->timestamps
                    ? [
                        'created' => $this->timestamps->created->format(DATE_ATOM),
                        'originalCreated' => $this->timestamps->originalCreated->format(DATE_ATOM),
                        'lastModified' => $this->timestamps->lastModified?->format(DATE_ATOM),
                        'originalLastModified' => $this->timestamps->originalLastModified?->format(DATE_ATOM),
                    ]
                    : null,
            ],
            fn (mixed $value): bool => $value !== null && $value !== false,
        );
    }
}
