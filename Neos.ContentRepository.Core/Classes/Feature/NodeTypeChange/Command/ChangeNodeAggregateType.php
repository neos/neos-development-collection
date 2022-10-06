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

/** @codingStandardsIgnoreStart */

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/** @codingStandardsIgnoreEnd */

/**
 * @api commands are the write-API of the ContentRepository
 */
final class ChangeNodeAggregateType implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $newNodeTypeName,
        public readonly NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy,
        /**
         * NodeAggregateIds for tethered descendants (optional).
         *
         * If the given node type declares tethered child nodes, you may predefine their node aggregate ids
         * using this assignment registry.
         * Since tethered child nodes may have tethered child nodes themselves,
         * this registry is indexed using relative node paths to the node to create in the first place.
         */
        public readonly ?NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds = null
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            NodeTypeName::fromString($array['newNodeTypeName']),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::from($array['strategy']),
            isset($array['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIds'])
                : null
        );
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return $this->contentStreamId === $nodeIdToPublish->contentStreamId
            && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId);
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->newNodeTypeName,
            $this->strategy,
            $this->tetheredDescendantNodeAggregateIds
        );
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
    public function withTetheredDescendantNodeAggregateIds(
        NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds
    ): self {
        return new self(
            $this->contentStreamId,
            $this->nodeAggregateId,
            $this->newNodeTypeName,
            $this->strategy,
            $tetheredDescendantNodeAggregateIds
        );
    }
}
