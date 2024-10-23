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
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The properties of {@see CreateNodeAggregateWithNode} are directly serialized; and then this command
 * is called and triggers the actual processing.
 *
 * @internal implementation detail, use {@see CreateNodeAggregateWithNode} instead.
 */
final readonly class CreateNodeAggregateWithNodeAndSerializedProperties implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
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
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $nodeTypeName,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public NodeAggregateId $parentNodeAggregateId,
        public SerializedPropertyValues $initialPropertyValues,
        public ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        public ?NodeName $nodeName,
        public NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of the node type of the new node
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint Origin of the new node in the dimension space. Will also be used to calculate a set of dimension points where the new node will cover from the configured specializations.
     * @param NodeAggregateId $parentNodeAggregateId The id of the node aggregate underneath which the new node is added
     * @param NodeAggregateId|null $succeedingSiblingNodeAggregateId Node aggregate id of the node's succeeding sibling (optional). If not given, the node will be added as the parent's first child
     * @param SerializedPropertyValues|null $initialPropertyValues The node's initial property values (serialized). Will be merged over the node type's default property values
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, NodeTypeName $nodeTypeName, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, NodeAggregateId $succeedingSiblingNodeAggregateId = null, SerializedPropertyValues $initialPropertyValues = null): self
    {
        return new self($workspaceName, $nodeAggregateId, $nodeTypeName, $originDimensionSpacePoint, $parentNodeAggregateId, $initialPropertyValues ?? SerializedPropertyValues::createEmpty(), $succeedingSiblingNodeAggregateId, null, NodeAggregateIdsByNodePaths::createEmpty());
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
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
     * We precalculate all $tetheredDescendantNodeAggregateIds via {@see NodeAggregateIdsByNodePaths::completeForNodeOfType}
     * so that when rebasing the command, it stays fully deterministic.
     *
     * See also the documentation of the higher level API {@see CreateNodeAggregateWithNode::withTetheredDescendantNodeAggregateIds}
     */
    public function withTetheredDescendantNodeAggregateIds(
        NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds
    ): self {
        return new self(
            $this->workspaceName,
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
     * The node's optional name.
     * Set if there is a meaningful relation to its parent that should be named.
     *
     * @deprecated the concept regarding node-names for non-tethered nodes is outdated.
     */
    public function withNodeName(NodeName $nodeName): self
    {
        return new self(
            $this->workspaceName,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->parentNodeAggregateId,
            $this->initialPropertyValues,
            $this->succeedingSiblingNodeAggregateId,
            $nodeName,
            $this->tetheredDescendantNodeAggregateIds,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
                && $nodeIdToPublish->dimensionSpacePoint?->equals($this->originDimensionSpacePoint)
        );
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
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
}
