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

namespace Neos\ContentRepository\Feature\NodeTypeChange\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;

final class ChangeNodeAggregateType implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    private NodeTypeName $newNodeTypeName;

    private NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * NodeAggregateIdentifiers for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate identifiers
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     */
    private ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $newNodeTypeName,
        NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy,
        UserIdentifier $initiatingUserIdentifier,
        ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeTypeName = $newNodeTypeName;
        $this->strategy = $strategy;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->tetheredDescendantNodeAggregateIdentifiers = $tetheredDescendantNodeAggregateIdentifiers;
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['newNodeTypeName']),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::from($array['strategy']),
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            isset($array['tetheredDescendantNodeAggregateIdentifiers'])
                ? NodeAggregateIdentifiersByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIdentifiers'])
                : null
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNewNodeTypeName(): NodeTypeName
    {
        return $this->newNodeTypeName;
    }

    public function getStrategy(): ?NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return $this->strategy;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getTetheredDescendantNodeAggregateIdentifiers(): ?NodeAggregateIdentifiersByNodePaths
    {
        return $this->tetheredDescendantNodeAggregateIdentifiers;
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        return $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
            && $this->nodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier);
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateIdentifier,
            $this->newNodeTypeName,
            $this->strategy,
            $this->initiatingUserIdentifier,
            $this->tetheredDescendantNodeAggregateIdentifiers
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'newNodeTypeName' => $this->newNodeTypeName,
            'strategy' => $this->strategy,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'tetheredDescendantNodeAggregateIdentifiers' => $this->tetheredDescendantNodeAggregateIdentifiers
        ];
    }

    /**
     * Create a new ChangeNodeAggregateType command with all original values,
     * except the tetheredDescendantNodeAggregateIdentifiers (where the passed in arguments are used).
     *
     * Is needed to make this command fully deterministic before storing it at the events.
     */
    public function withTetheredDescendantNodeAggregateIdentifiers(
        NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers
    ): self {
        return new self(
            $this->contentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->newNodeTypeName,
            $this->strategy,
            $this->initiatingUserIdentifier,
            $tetheredDescendantNodeAggregateIdentifiers
        );
    }
}
