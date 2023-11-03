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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Set property values for a given node (internal implementation).
 *
 * The property values contain the serialized types already, and include type information.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class SetSerializedNodeReferences implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $sourceNodeAggregateId The identifier of the node aggregate to set references
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint The dimension space for which the references should be set
     * @param ReferenceName $referenceName Name of the reference to set
     * @param SerializedNodeReferences $references Serialized reference(s) to set
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $sourceNodeAggregateId,
        public OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        public ReferenceName $referenceName,
        public SerializedNodeReferences $references,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $sourceNodeAggregateId The identifier of the node aggregate to set references
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint The dimension space for which the references should be set
     * @param ReferenceName $referenceName Name of the reference to set
     * @param SerializedNodeReferences $references Serialized reference(s) to set
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $sourceNodeAggregateId, OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint, ReferenceName $referenceName, SerializedNodeReferences $references): self
    {
        return new self($workspaceName, $sourceNodeAggregateId, $sourceOriginDimensionSpacePoint, $referenceName, $references);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['sourceNodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['sourceOriginDimensionSpacePoint']),
            ReferenceName::fromString($array['referenceName']),
            SerializedNodeReferences::fromArray($array['references']),
        );
    }

    /**
     * @internal
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->workspaceName === $nodeIdToPublish->workspaceName
                && $this->sourceOriginDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
                && $this->sourceNodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
        ContentStreamId $targetContentStreamId
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->sourceNodeAggregateId,
            $this->sourceOriginDimensionSpacePoint,
            $this->referenceName,
            $this->references,
        );
    }
}
