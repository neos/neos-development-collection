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
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
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
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the affected node aggregate
     * @param OriginDimensionSpacePoint $sourceOrigin Dimension Space Point from which the node is to be copied from
     * @param OriginDimensionSpacePoint $targetOrigin Dimension Space Point to which the node is to be copied to
     * @param ?NodeAggregateId $parentNodeAggregateId The optional id of the node aggregate to be used as the variant's parent
     * @param ?NodeAggregateId $succeedingSiblingNodeAggregateId The optional id of the node aggregate to be used as the variant's succeeding sibling
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $sourceOrigin,
        public OriginDimensionSpacePoint $targetOrigin,
        public ?NodeAggregateId $parentNodeAggregateId,
        public ?NodeAggregateId $precedingSiblingNodeAggregateId,
        public ?NodeAggregateId $succeedingSiblingNodeAggregateId,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the affected node aggregate
     * @param OriginDimensionSpacePoint $sourceOrigin Dimension Space Point from which the node is to be copied from
     * @param OriginDimensionSpacePoint $targetOrigin Dimension Space Point to which the node is to be copied to
     * @param ?NodeAggregateId $parentNodeAggregateId The optional id of the node aggregate to be used as the variant's parent
     * @param ?NodeAggregateId $precedingSiblingNodeAggregateId The optional id of the node aggregate to be used as the variant's preceding sibling
     * @param ?NodeAggregateId $succeedingSiblingNodeAggregateId The optional id of the node aggregate to be used as the variant's succeeding sibling
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $targetOrigin, ?NodeAggregateId $parentNodeAggregateId = null, ?NodeAggregateId $precedingSiblingNodeAggregateId = null, ?NodeAggregateId $succeedingSiblingNodeAggregateId = null): self
    {
        return new self($workspaceName, $nodeAggregateId, $sourceOrigin, $targetOrigin, $parentNodeAggregateId, $precedingSiblingNodeAggregateId, $succeedingSiblingNodeAggregateId);
    }

    public static function fromArray(array $array): self
    {
        return new self(
            workspaceName: WorkspaceName::fromString($array['workspaceName']),
            nodeAggregateId: NodeAggregateId::fromString($array['nodeAggregateId']),
            sourceOrigin: OriginDimensionSpacePoint::fromArray($array['sourceOrigin']),
            targetOrigin: OriginDimensionSpacePoint::fromArray($array['targetOrigin']),
            parentNodeAggregateId: (is_string($parentNodeAggregateId = $array['parentNodeAggregateId'] ?? null))
                ? NodeAggregateId::fromString($parentNodeAggregateId)
                : null,
            precedingSiblingNodeAggregateId: (is_string($precedingSiblingNodeAggregateId = $array['precedingSiblingNodeAggregateId'] ?? null))
                ? NodeAggregateId::fromString($precedingSiblingNodeAggregateId)
                : null,
            succeedingSiblingNodeAggregateId: (is_string($succeedingSiblingNodeAggregateId = $array['succeedingSiblingNodeAggregateId'] ?? null))
                ? NodeAggregateId::fromString($succeedingSiblingNodeAggregateId)
                : null,
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->sourceOrigin,
            $this->targetOrigin,
            $this->parentNodeAggregateId,
            $this->precedingSiblingNodeAggregateId,
            $this->succeedingSiblingNodeAggregateId
        );
    }
}
