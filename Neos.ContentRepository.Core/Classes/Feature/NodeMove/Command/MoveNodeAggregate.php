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

namespace Neos\ContentRepository\Core\Feature\NodeMove\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The "Move node aggregate" command
 *
 * In `contentStreamId`
 * and `dimensionSpacePoint`,
 * move node aggregate `nodeAggregateId`
 * into `newParentNodeAggregateId` (or keep the current parent)
 * between `newPrecedingSiblingNodeAggregateId`
 * and `newSucceedingSiblingNodeAggregateId` (or as last of all siblings)
 * using `relationDistributionStrategy`
 *
 * Why can you specify **both** newPrecedingSiblingNodeAggregateId
 * and newSucceedingSiblingNodeAggregateId?
 * - it can happen that in one subgraph, only one of these match.
 * - See the PHPDoc of the attributes (a few lines down) for the exact behavior.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class MoveNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        /**
         * The content stream in which the move operation is to be performed
         */
        public readonly ContentStreamId $contentStreamId,
        /**
         * This is one of the *covered* dimension space points of the node aggregate
         * and not necessarily one of the occupied ones.
         * This allows us to move virtual specializations only when using the scatter strategy.
         */
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        /**
         * The node aggregate to be moved
         */
        public readonly NodeAggregateId $nodeAggregateId,
        /**
         * This is the id of the new parent node aggregate.
         * If given, it enforces that all nodes in the given aggregate are moved into nodes of the parent aggregate,
         * even if the given siblings belong to other parents. In latter case, those siblings are ignored.
         */
        public readonly ?NodeAggregateId $newParentNodeAggregateId,
        /**
         * This is the id of the new preceding sibling node aggregate.
         * If given and no successor found, it is attempted to insert the moved nodes right after nodes of this
         * aggregate.
         * In dimension space points this aggregate does not cover, other siblings,
         * in order of proximity, are tried to be used instead.
         */
        public readonly ?NodeAggregateId $newPrecedingSiblingNodeAggregateId,
        /**
         * This is the id of the new succeeding sibling node aggregate.
         * If given, it is attempted to insert the moved nodes right before nodes of this aggregate.
         * In dimension space points this aggregate does not cover, the preceding sibling is tried to be used instead.
         */
        public readonly ?NodeAggregateId $newSucceedingSiblingNodeAggregateId,
        /**
         * The relation distribution strategy to be used
         */
        public readonly RelationDistributionStrategy $relationDistributionStrategy,
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            isset($array['newParentNodeAggregateId'])
                ? NodeAggregateId::fromString($array['newParentNodeAggregateId'])
                : null,
            isset($array['newPrecedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($array['newPrecedingSiblingNodeAggregateId'])
                : null,
            isset($array['newSucceedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($array['newSucceedingSiblingNodeAggregateId'])
                : null,
            RelationDistributionStrategy::fromString($array['relationDistributionStrategy']),
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
            $this->dimensionSpacePoint,
            $this->nodeAggregateId,
            $this->newParentNodeAggregateId,
            $this->newPrecedingSiblingNodeAggregateId,
            $this->newSucceedingSiblingNodeAggregateId,
            $this->relationDistributionStrategy,
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return $this->contentStreamId === $nodeIdToPublish->contentStreamId
            && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
            && $this->dimensionSpacePoint === $nodeIdToPublish->dimensionSpacePoint;
    }
}
