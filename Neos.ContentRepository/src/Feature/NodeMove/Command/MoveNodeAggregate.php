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

namespace Neos\ContentRepository\Feature\NodeMove\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\Feature\NodeMove\Command\RelationDistributionStrategy;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * The "Move node aggregate" command
 *
 * In `contentStreamIdentifier`
 * and `dimensionSpacePoint`,
 * move node aggregate `nodeAggregateIdentifier`
 * into `newParentNodeAggregateIdentifier` (or keep the current parent)
 * between `newPrecedingSiblingNodeAggregateIdentifier`
 * and `newSucceedingSiblingNodeAggregateIdentifier` (or as last of all siblings)
 * using `relationDistributionStrategy`
 * initiated by `initiatingUserIdentifier`
 *
 * Why can you specify **both** newPrecedingSiblingNodeAggregateIdentifier
 * and newSucceedingSiblingNodeAggregateIdentifier?
 * - it can happen that in one subgraph, only one of these match.
 * - See the PHPDoc of the attributes (a few lines down) for the exact behavior.
 */
final class MoveNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface
{
    /**
     * The content stream in which the move operation is to be performed
     */
    private ContentStreamIdentifier $contentStreamIdentifier;

    /**
     * This is one of the *covered* dimension space points of the node aggregate
     * and not necessarily one of the occupied ones.
     * This allows us to move virtual specializations only when using the scatter strategy.
     */
    private DimensionSpacePoint $dimensionSpacePoint;

    /**
     * The node aggregate to be moved
     */
    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    /**
     * This is the identifier of the new parent node aggregate.
     * If given, it enforces that all nodes in the given aggregate are moved into nodes of the parent aggregate,
     * even if the given siblings belong to other parents. In latter case, those siblings are ignored.
     */
    private ?NodeAggregateIdentifier $newParentNodeAggregateIdentifier;

    /**
     * This is the identifier of the new preceding sibling node aggregate.
     * If given and no successor found, it is attempted to insert the moved nodes right after nodes of this aggregate.
     * In dimension space points this aggregate does not cover, other siblings,
     * in order of proximity, are tried to be used instead.
     */
    private ?NodeAggregateIdentifier $newPrecedingSiblingNodeAggregateIdentifier;

    /**
     * This is the identifier of the new succeeding sibling node aggregate.
     * If given, it is attempted to insert the moved nodes right before nodes of this aggregate.
     * In dimension space points this aggregate does not cover, the preceding sibling is tried to be used instead.
     */
    private ?NodeAggregateIdentifier $newSucceedingSiblingNodeAggregateIdentifier;

    /**
     * The relation distribution strategy to be used
     */
    private RelationDistributionStrategy $relationDistributionStrategy;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newPrecedingSiblingNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newSucceedingSiblingNodeAggregateIdentifier,
        RelationDistributionStrategy $relationDistributionStrategy,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newParentNodeAggregateIdentifier = $newParentNodeAggregateIdentifier;
        $this->newPrecedingSiblingNodeAggregateIdentifier = $newPrecedingSiblingNodeAggregateIdentifier;
        $this->newSucceedingSiblingNodeAggregateIdentifier = $newSucceedingSiblingNodeAggregateIdentifier;
        $this->relationDistributionStrategy = $relationDistributionStrategy;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            isset($array['newParentNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($array['newParentNodeAggregateIdentifier'])
                : null,
            isset($array['newPrecedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($array['newPrecedingSiblingNodeAggregateIdentifier'])
                : null,
            isset($array['newSucceedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($array['newSucceedingSiblingNodeAggregateIdentifier'])
                : null,
            RelationDistributionStrategy::fromString($array['relationDistributionStrategy']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNewParentNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newParentNodeAggregateIdentifier;
    }

    public function getNewPrecedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newPrecedingSiblingNodeAggregateIdentifier;
    }

    public function getNewSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newSucceedingSiblingNodeAggregateIdentifier;
    }

    public function getRelationDistributionStrategy(): RelationDistributionStrategy
    {
        return $this->relationDistributionStrategy;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'newParentNodeAggregateIdentifier' => $this->newParentNodeAggregateIdentifier,
            'newPrecedingSiblingNodeAggregateIdentifier' => $this->newPrecedingSiblingNodeAggregateIdentifier,
            'newSucceedingSiblingNodeAggregateIdentifier' => $this->newSucceedingSiblingNodeAggregateIdentifier,
            'relationDistributionStrategy' => $this->relationDistributionStrategy,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->dimensionSpacePoint,
            $this->nodeAggregateIdentifier,
            $this->newParentNodeAggregateIdentifier,
            $this->newPrecedingSiblingNodeAggregateIdentifier,
            $this->newSucceedingSiblingNodeAggregateIdentifier,
            $this->relationDistributionStrategy,
            $this->initiatingUserIdentifier
        );
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        return $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
            && $this->nodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier)
            && $this->dimensionSpacePoint === $nodeIdentifierToPublish->dimensionSpacePoint;
    }
}
