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

namespace Neos\ContentRepository\Core\Feature\NodeVariation\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Create a variant of a node in a content stream
 *
 * Copy a node to another dimension space point respecting further variation mechanisms
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class CreateNodeVariant implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the affected node aggregate
     * @param OriginDimensionSpacePoint $sourceOrigin Dimension Space Point from which the node is to be copied from
     * @param OriginDimensionSpacePoint $targetOrigin Dimension Space Point to which the node is to be copied to
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $sourceOrigin,
        public OriginDimensionSpacePoint $targetOrigin,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the affected node aggregate
     * @param OriginDimensionSpacePoint $sourceOrigin Dimension Space Point from which the node is to be copied from
     * @param OriginDimensionSpacePoint $targetOrigin Dimension Space Point to which the node is to be copied to
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $targetOrigin): self
    {
        return new self($workspaceName, $nodeAggregateId, $sourceOrigin, $targetOrigin);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['sourceOrigin']),
            OriginDimensionSpacePoint::fromArray($array['targetOrigin']),
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
        return $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
            && $this->targetOrigin->equals($nodeIdToPublish->dimensionSpacePoint);
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->sourceOrigin,
            $this->targetOrigin,
        );
    }
}
