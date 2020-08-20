<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategyIsUnknown;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;

final class ChangeNodeAggregateType implements \JsonSerializable, RebasableToOtherContentStreamsInterface, MatchableWithNodeAddressInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var NodeTypeName
     */
    protected $newNodeTypeName;

    /**
     * @var NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
     */
    protected $strategy;

    /**
     * NodeAggregateIdentifiers for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate identifiers
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     *
     * @var NodeAggregateIdentifiersByNodePaths|null
     */
    private ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers;

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $newNodeTypeName, NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $strategy, ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers = null)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeTypeName = $newNodeTypeName;
        $this->strategy = $strategy;
        $this->tetheredDescendantNodeAggregateIdentifiers = $tetheredDescendantNodeAggregateIdentifiers;
    }

    /**
     * @param array $array
     * @return ChangeNodeAggregateType
     * @throws NodeAggregateTypeChangeChildConstraintConflictResolutionStrategyIsUnknown
     */
    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['newNodeTypeName']),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::fromString($array['strategy']),
            isset($array['tetheredDescendantNodeAggregateIdentifiers'])
                ? NodeAggregateIdentifiersByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIdentifiers'])
                : null
        );
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return NodeTypeName
     */
    public function getNewNodeTypeName(): NodeTypeName
    {
        return $this->newNodeTypeName;
    }

    /**
     * @return NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy|null
     */
    public function getStrategy(): ?NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy
    {
        return $this->strategy;
    }

    public function getTetheredDescendantNodeAggregateIdentifiers(): ?NodeAggregateIdentifiersByNodePaths
    {
        return $this->tetheredDescendantNodeAggregateIdentifiers;
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->contentStreamIdentifier === (string)$nodeAddress->getContentStreamIdentifier()
            && (string)$this->nodeAggregateIdentifier === (string)$nodeAddress->getNodeAggregateIdentifier()
        );

    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier)
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->newNodeTypeName,
            $this->strategy,
            $this->tetheredDescendantNodeAggregateIdentifiers
        );

    }

    public function jsonSerialize()
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'newNodeTypeName' => $this->newNodeTypeName,
            'strategy' => $this->strategy,
            'tetheredDescendantNodeAggregateIdentifiers' => $this->tetheredDescendantNodeAggregateIdentifiers
        ];
    }

    /**
     * Create a new ChangeNodeAggregateType command with all original values, except the tetheredDescendantNodeAggregateIdentifiers (where
     * the passed in arguments are used).
     *
     * Is needed to make this command fully deterministic before storing it at the events.
     *
     * @param NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers
     * @return CreateNodeAggregateWithNode
     */
    public function withTetheredDescendantNodeAggregateIdentifiers(NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers): self
    {
        return new self(
            $this->contentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->newNodeTypeName,
            $this->strategy,
            $tetheredDescendantNodeAggregateIdentifiers
        );
    }

}
