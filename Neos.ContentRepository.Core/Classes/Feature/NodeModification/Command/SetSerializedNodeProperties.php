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

namespace Neos\ContentRepository\Core\Feature\NodeModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Set property values for a given node (internal implementation).
 *
 * The property values contain the serialized types already, and include type information.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class SetSerializedNodeProperties implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the set properties operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate to set the properties for
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint The dimension space point the properties should be changed in
     * @param SerializedPropertyValues $propertyValues Names and (serialized) values of properties to set
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public SerializedPropertyValues $propertyValues,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the set properties operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate to set the properties for
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint The dimension space point the properties should be changed in
     * @param SerializedPropertyValues $propertyValues Names and (serialized) values of properties to set
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $originDimensionSpacePoint, SerializedPropertyValues $propertyValues): self
    {
        return new self($workspaceName, $nodeAggregateId, $originDimensionSpacePoint, $propertyValues);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            SerializedPropertyValues::fromArray($array['propertyValues']),
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
                && $this->originDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
                && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
        ContentStreamId $targetContentStreamId
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->originDimensionSpacePoint,
            $this->propertyValues
        );
    }
}
