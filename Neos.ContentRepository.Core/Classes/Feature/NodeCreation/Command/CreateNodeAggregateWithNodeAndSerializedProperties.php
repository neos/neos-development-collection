<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeCreation\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The properties of {@see CreateNodeAggregateWithNode} are directly serialized; and then this command
 * is called and triggers the actual processing.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class CreateNodeAggregateWithNodeAndSerializedProperties implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    /**
     * @param ContentStreamId $contentStreamId The content stream in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of the node type of the new node
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint Origin of the new node in the dimension space. Will also be used to calculate a set of dimension points where the new node will cover from the configured specializations.
     * @param NodeAggregateId $parentNodeAggregateId The id of the node aggregate underneath which the new node is added
     * @param SerializedPropertyValues $initialPropertyValues The node's initial property values (serialized). Will be merged over the node type's default property values
     * @param NodeAggregateId|null $succeedingSiblingNodeAggregateId Node aggregate id of the node's succeeding sibling (optional). If not given, the node will be added as the parent's first child
     * @param NodeName|null $nodeName The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     * @param NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds Predefined aggregate ids of tethered child nodes per path. For any tethered node that has no matching entry in this set, the node aggregate id is generated randomly. Since tethered nodes may have tethered child nodes themselves, this works for multiple levels ({@see self::withTetheredDescendantNodeAggregateIds()})
     */
    private function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $nodeTypeName,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly NodeAggregateId $parentNodeAggregateId,
        public readonly SerializedPropertyValues $initialPropertyValues,
        public readonly ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        public readonly ?NodeName $nodeName,
        public readonly NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The content stream in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of the node type of the new node
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint Origin of the new node in the dimension space. Will also be used to calculate a set of dimension points where the new node will cover from the configured specializations.
     * @param NodeAggregateId $parentNodeAggregateId The id of the node aggregate underneath which the new node is added
     * @param NodeAggregateId|null $succeedingSiblingNodeAggregateId Node aggregate id of the node's succeeding sibling (optional). If not given, the node will be added as the parent's first child
     * @param NodeName|null $nodeName The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     * @param SerializedPropertyValues|null $initialPropertyValues The node's initial property values (serialized). Will be merged over the node type's default property values
     */
    public static function create(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, NodeTypeName $nodeTypeName, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, NodeAggregateId $succeedingSiblingNodeAggregateId = null, NodeName $nodeName = null, SerializedPropertyValues $initialPropertyValues = null): self
    {
        return new self($contentStreamId, $nodeAggregateId, $nodeTypeName, $originDimensionSpacePoint, $parentNodeAggregateId, $initialPropertyValues ?? SerializedPropertyValues::createEmpty(), $succeedingSiblingNodeAggregateId, $nodeName, NodeAggregateIdsByNodePaths::createEmpty());
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            NodeTypeName::fromString($array['nodeTypeName']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            NodeAggregateId::fromString($array['parentNodeAggregateId']),
            isset($array['initialPropertyValues'])
                ? SerializedPropertyValues::fromArray($array['initialPropertyValues'])
                : SerializedPropertyValues::createEmpty(),
            isset($array['succeedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($array['succeedingSiblingNodeAggregateId'])
                : null,
            isset($array['nodeName'])
                ? NodeName::fromString($array['nodeName'])
                : null,
            isset($array['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIds'])
                : NodeAggregateIdsByNodePaths::createEmpty()
        );
    }

    /**
     * Create a new CreateNodeAggregateWithNode command with all original values,
     * except the tetheredDescendantNodeAggregateIds (where the passed in arguments are used).
     *
     * Is needed to make this command fully deterministic before storing it at the events
     * - we need this
     */
    public function withTetheredDescendantNodeAggregateIds(
        NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds
    ): self {
        return new self(
            $this->contentStreamId,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->parentNodeAggregateId,
            $this->initialPropertyValues,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $tetheredDescendantNodeAggregateIds
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->parentNodeAggregateId,
            $this->initialPropertyValues,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $this->tetheredDescendantNodeAggregateIds
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->contentStreamId === $nodeIdToPublish->contentStreamId
                && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
                && $this->originDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
        );
    }
}
