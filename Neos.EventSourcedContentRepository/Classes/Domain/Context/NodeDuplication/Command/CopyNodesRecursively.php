<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RebasableToOtherContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\Dto\NodeAggregateIdentifierMapping;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\Dto\NodeSubtreeSnapshot;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * CopyNodesRecursively command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateIdentifier` and `nodeIdentifier`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must cover the given
 * `dimensionSpacePoint`.
 *
 * @Flow\Proxy(false)
 */
final class CopyNodesRecursively implements \JsonSerializable, MatchableWithNodeAddressInterface, RebasableToOtherContentStreamsInterface
{
    /**
     * The identifier of the content stream this command is to be handled in
     *
     * @var ContentStreamIdentifier
     */
    private ContentStreamIdentifier $contentStreamIdentifier;

    /**
     * The to be copied node's node aggregate identifier
     *
     * @var NodeSubtreeSnapshot
     */
    private NodeSubtreeSnapshot $nodeToInsert;

    /**
     * the dimension space point which is the target of the copy
     *
     * @var OriginDimensionSpacePoint
     */
    private OriginDimensionSpacePoint $targetDimensionSpacePoint;

    /**
     * The initiating user's identifier
     *
     * @var UserIdentifier
     */
    private UserIdentifier $initiatingUserIdentifier;

    /**
     * Node aggregate identifier of the target node's parent (optional)
     *
     * If not given, the node will be added as a root node
     *
     * @var NodeAggregateIdentifier
     */
    private NodeAggregateIdentifier $targetParentNodeAggregateIdentifier;

    /**
     * Node aggregate identifier of the target node's succeeding sibling (optional)
     *
     * If not given, the node will be added as the parent's first child
     *
     * @var NodeAggregateIdentifier|null
     */
    private ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier;

    /**
     * the root node name of the root-inserted-node
     *
     * @var NodeName|null
     */
    private ?NodeName $targetNodeName;

    private NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping;

    private function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeSubtreeSnapshot $nodeToInsert,
        OriginDimensionSpacePoint $targetDimensionSpacePoint,
        UserIdentifier $initiatingUserIdentifier,
        NodeAggregateIdentifier $targetParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier,
        ?NodeName $targetNodeName,
        NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeToInsert = $nodeToInsert;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->targetParentNodeAggregateIdentifier = $targetParentNodeAggregateIdentifier;
        $this->targetSucceedingSiblingNodeAggregateIdentifier = $targetSucceedingSiblingNodeAggregateIdentifier;
        $this->targetNodeName = $targetNodeName;
        $this->nodeAggregateIdentifierMapping = $nodeAggregateIdentifierMapping;
    }

    /**
     * @todo reference start node by address instead of passing it
     */
    public static function create(
        ContentSubgraphInterface $subgraph,
        NodeInterface $startNode,
        OriginDimensionSpacePoint $dimensionSpacePoint,
        UserIdentifier $initiatingUserIdentifier,
        NodeAggregateIdentifier $targetParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier,
        ?NodeName $targetNodeName
    ) {
        $nodeSubtreeSnapshot = NodeSubtreeSnapshot::fromSubgraphAndStartNode($subgraph, $startNode);

        return new static(
            $subgraph->getContentStreamIdentifier(),
            $nodeSubtreeSnapshot,
            $dimensionSpacePoint,
            $initiatingUserIdentifier,
            $targetParentNodeAggregateIdentifier,
            $targetSucceedingSiblingNodeAggregateIdentifier,
            $targetNodeName,
            NodeAggregateIdentifierMapping::generateForNodeSubtreeSnapshot($nodeSubtreeSnapshot)
        );
    }


    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeSubtreeSnapshot::fromArray($array['nodeToInsert']),
            new OriginDimensionSpacePoint($array['targetDimensionSpacePoint']),
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            NodeAggregateIdentifier::fromString($array['targetParentNodeAggregateIdentifier']),
            isset($array['targetSucceedingSiblingNodeAggregateIdentifier']) ? NodeAggregateIdentifier::fromString($array['targetSucceedingSiblingNodeAggregateIdentifier']) : null,
            isset($array['targetNodeName']) ? NodeName::fromString($array['targetNodeName']) : null,
            NodeAggregateIdentifierMapping::fromArray($array['nodeAggregateIdentifierMapping'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeToInsert(): NodeSubtreeSnapshot
    {
        return $this->nodeToInsert;
    }

    public function getTargetDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->targetDimensionSpacePoint;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getTargetParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->targetParentNodeAggregateIdentifier;
    }

    public function getTargetSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->targetSucceedingSiblingNodeAggregateIdentifier;
    }

    public function getTargetNodeName(): ?NodeName
    {
        return $this->targetNodeName;
    }

    public function getNodeAggregateIdentifierMapping(): NodeAggregateIdentifierMapping
    {
        return $this->nodeAggregateIdentifierMapping;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeToInsert' => $this->nodeToInsert,
            'targetDimensionSpacePoint' => $this->targetDimensionSpacePoint,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'targetParentNodeAggregateIdentifier' => $this->targetParentNodeAggregateIdentifier,
            'targetSucceedingSiblingNodeAggregateIdentifier' => $this->targetSucceedingSiblingNodeAggregateIdentifier,
            'targetNodeName' => $this->targetNodeName,
            'nodeAggregateIdentifierMapping' => $this->nodeAggregateIdentifierMapping,
        ];
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        $targetNodeAggregateIdentifier = $this->getNodeAggregateIdentifierMapping()->getNewNodeAggregateIdentifier($this->getNodeToInsert()->getNodeAggregateIdentifier());
        return (
            $this->getContentStreamIdentifier()->equals($nodeAddress->getContentStreamIdentifier())
            && $this->getTargetDimensionSpacePoint()->equals($nodeAddress->getDimensionSpacePoint())
            && $targetNodeAggregateIdentifier->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeToInsert,
            $this->targetDimensionSpacePoint,
            $this->initiatingUserIdentifier,
            $this->targetParentNodeAggregateIdentifier,
            $this->targetSucceedingSiblingNodeAggregateIdentifier,
            $this->targetNodeName,
            $this->nodeAggregateIdentifierMapping
        );
    }
}
