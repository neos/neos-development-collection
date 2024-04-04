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

namespace Neos\ContentRepository\Core\Feature\NodeTypeChange\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api commands are the write-API of the ContentRepository
 */
final readonly class ChangeNodeAggregateType implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to change
     * @param NodeTypeName $newNodeTypeName Name of the new node type
     * @param NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy Strategy for conflicts on affected child nodes ({@see NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy})
     * @param NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds Predefined aggregate ids of any tethered child nodes for the new node type per path. For any tethered node that has no matching entry in this set, the node aggregate id is generated randomly. Since tethered nodes may have tethered child nodes themselves, this works for multiple levels ({@see self::withTetheredDescendantNodeAggregateIds()})
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $newNodeTypeName,
        public NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy,
        public NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to change
     * @param NodeTypeName $newNodeTypeName Name of the new node type
     * @param NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy Strategy for conflicts on affected child nodes ({@see NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy})
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, NodeTypeName $newNodeTypeName, NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy): self
    {
        return new self($workspaceName, $nodeAggregateId, $newNodeTypeName, $strategy, NodeAggregateIdsByNodePaths::createEmpty());
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            NodeTypeName::fromString($array['newNodeTypeName']),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::from($array['strategy']),
            isset($array['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIds'])
                : NodeAggregateIdsByNodePaths::createEmpty()
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId);
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * Create a new ChangeNodeAggregateType command with all original values,
     * except the tetheredDescendantNodeAggregateIds (where the passed in arguments are used).
     *
     * Is needed to make this command fully deterministic before storing it at the events.
     */
    public function withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds): self
    {
        return new self(
            $this->workspaceName,
            $this->nodeAggregateId,
            $this->newNodeTypeName,
            $this->strategy,
            $tetheredDescendantNodeAggregateIds
        );
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->newNodeTypeName,
            $this->strategy,
            $this->tetheredDescendantNodeAggregateIds
        );
    }
}
