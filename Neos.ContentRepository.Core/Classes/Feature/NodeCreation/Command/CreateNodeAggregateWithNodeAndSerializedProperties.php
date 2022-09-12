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
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\User\UserId;

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
     * The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     */
    public readonly ?NodeName $nodeName;

    /**
     * Node aggregate identifier of the node's succeeding sibling (optional)
     * If not given, the node will be added as the parent's first child
     */
    public readonly ?NodeAggregateId $succeedingSiblingNodeAggregateId;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     */
    public readonly SerializedPropertyValues $initialPropertyValues;

    /**
     * NodeAggregateIds for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate ids
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     */
    public readonly NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds;

    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $nodeTypeName,
        /**
         * Origin of the new node in the dimension space.
         * Will also be used to calculate a set of dimension points where the new node will cover
         * from the configured specializations.
         */
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly UserId $initiatingUserId,
        public readonly NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId = null,
        ?NodeName $nodeName = null,
        ?SerializedPropertyValues $initialPropertyValues = null,
        ?NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds = null
    ) {
        $this->succeedingSiblingNodeAggregateId = $succeedingSiblingNodeAggregateId;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues ?: SerializedPropertyValues::fromArray([]);
        $this->tetheredDescendantNodeAggregateIds = $tetheredDescendantNodeAggregateIds
            ?: new NodeAggregateIdsByNodePaths([]);
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
            UserId::fromString($array['initiatingUserId']),
            NodeAggregateId::fromString($array['parentNodeAggregateId']),
            isset($array['succeedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($array['succeedingSiblingNodeAggregateId'])
                : null,
            isset($array['nodeName'])
                ? NodeName::fromString($array['nodeName'])
                : null,
            isset($array['initialPropertyValues'])
                ? SerializedPropertyValues::fromArray($array['initialPropertyValues'])
                : null,
            isset($array['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIds'])
                : null
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
            $this->initiatingUserId,
            $this->parentNodeAggregateId,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $this->initialPropertyValues,
            $tetheredDescendantNodeAggregateIds
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'nodeTypeName' => $this->nodeTypeName,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
            'initiatingUserId' => $this->initiatingUserId,
            'parentNodeAggregateId' => $this->parentNodeAggregateId,
            'succeedingSiblingNodeAggregateId' => $this->succeedingSiblingNodeAggregateId,
            'nodeName' => $this->nodeName,
            'initialPropertyValues' => $this->initialPropertyValues,
            'tetheredDescendantNodeAggregateIds' => $this->tetheredDescendantNodeAggregateIds
        ];
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->initiatingUserId,
            $this->parentNodeAggregateId,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $this->initialPropertyValues,
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
