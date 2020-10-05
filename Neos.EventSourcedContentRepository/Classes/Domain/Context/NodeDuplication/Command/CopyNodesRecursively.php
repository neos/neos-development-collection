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
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
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
    private $contentStreamIdentifier;

    /**
     * The to be copied node's node aggregate identifier
     *
     * @var NodeSubtreeSnapshot
     */
    private $nodeToInsert;

    /**
     * the dimension space point which is the target of the copy
     *
     * @var OriginDimensionSpacePoint
     */
    private $targetDimensionSpacePoint;

    /**
     * The initiating user's identifier
     *
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * Node aggregate identifier of the target node's parent (optional)
     *
     * If not given, the node will be added as a root node
     *
     * @var NodeAggregateIdentifier
     */
    private $targetParentNodeAggregateIdentifier;

    /**
     * Node aggregate identifier of the target node's succeeding sibling (optional)
     *
     * If not given, the node will be added as the parent's first child
     *
     * @var NodeAggregateIdentifier
     */
    private $targetSucceedingSiblingNodeAggregateIdentifier;

    /**
     * the root node name of the root-inserted-node
     *
     * @var NodeName
     */
    private $targetNodeName;

    /**
     * @var NodeAggregateIdentifierMapping
     */
    private $nodeAggregateIdentifierMapping;

    /**
     * CopyNodesRecursively constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeSubtreeSnapshot $nodeToInsert
     * @param OriginDimensionSpacePoint $targetDimensionSpacePoint
     * @param UserIdentifier $initiatingUserIdentifier
     * @param NodeAggregateIdentifier $targetParentNodeAggregateIdentifier
     * @param NodeAggregateIdentifier|null $targetSucceedingSiblingNodeAggregateIdentifier
     * @param NodeName|null $targetNodeName
     * @param NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping
     */
    private function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeSubtreeSnapshot $nodeToInsert, OriginDimensionSpacePoint $targetDimensionSpacePoint, UserIdentifier $initiatingUserIdentifier, NodeAggregateIdentifier $targetParentNodeAggregateIdentifier, ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier, ?NodeName $targetNodeName, NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeToInsert = $nodeToInsert;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->targetParentNodeAggregateIdentifier = $targetParentNodeAggregateIdentifier;
        $this->targetSucceedingSiblingNodeAggregateIdentifier = $targetSucceedingSiblingNodeAggregateIdentifier;
        $this->targetNodeName = $targetNodeName;
        $this->nodeAggregateIdentifierMapping = $nodeAggregateIdentifierMapping;
    }

    public static function create(TraversableNodeInterface $sourceNode, OriginDimensionSpacePoint $dimensionSpacePoint, UserIdentifier $initiatingUserIdentifier, NodeAggregateIdentifier $targetParentNodeAggregateIdentifier, ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier, ?NodeName $targetNodeName)
    {
        $nodeSubtreeSnapshot = NodeSubtreeSnapshot::fromTraversableNode($sourceNode);

        return new static(
            $sourceNode->getContentStreamIdentifier(),
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


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeSubtreeSnapshot
     */
    public function getNodeToInsert(): NodeSubtreeSnapshot
    {
        return $this->nodeToInsert;
    }

    /**
     * @return OriginDimensionSpacePoint
     */
    public function getTargetDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->targetDimensionSpacePoint;
    }

    /**
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getTargetParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->targetParentNodeAggregateIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getTargetSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->targetSucceedingSiblingNodeAggregateIdentifier;
    }

    /**
     * @return NodeName
     */
    public function getTargetNodeName(): NodeName
    {
        return $this->targetNodeName;
    }

    /**
     * @return NodeAggregateIdentifierMapping
     */
    public function getNodeAggregateIdentifierMapping(): NodeAggregateIdentifierMapping
    {
        return $this->nodeAggregateIdentifierMapping;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
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
            (string)$this->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
            && $this->getTargetDimensionSpacePoint()->equals($nodeAddress->getDimensionSpacePoint())
            && $targetNodeAggregateIdentifier->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new static(
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
