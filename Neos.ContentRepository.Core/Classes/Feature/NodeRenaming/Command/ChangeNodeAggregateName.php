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

namespace Neos\ContentRepository\Core\Feature\NodeRenaming\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * All variants in a NodeAggregate have the same NodeName - and this can be changed here.
 * This is the case because Node Names are usually only used for tethered nodes (=autocreated in the old CR);
 * as then the Node Name is used for querying.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class ChangeNodeAggregateName implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to rename
     * @param NodeName $newNodeName The new name of the node aggregate
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public NodeName $newNodeName,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to rename
     * @param NodeName $newNodeName The new name of the node aggregate
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, NodeName $newNodeName): self
    {
        return new self($workspaceName, $nodeAggregateId, $newNodeName);
    }

    /**
     * @param array<string,string> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            NodeName::fromString($array['newNodeName']),
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId);
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
        ContentStreamId $targetContentStreamId
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->newNodeName,
        );
    }
}
