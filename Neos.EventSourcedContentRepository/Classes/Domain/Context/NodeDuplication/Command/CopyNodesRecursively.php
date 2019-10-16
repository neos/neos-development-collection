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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\Dto\NodeToInsert;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * CopyNodesRecursively command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateIdentifier` and `nodeIdentifier`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must cover the given
 * `dimensionSpacePoint`.
 */
final class CopyNodesRecursively implements \JsonSerializable
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
     * @var NodeToInsert
     */
    private $nodeToInsert;

    /**
     * the dimension space point where the node will be copied from; and which is also the target of the copy.
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

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
     * CopyNodesRecursively constructor.
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeToInsert $nodeToInsert
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param UserIdentifier $initiatingUserIdentifier
     * @param NodeAggregateIdentifier $targetParentNodeAggregateIdentifier
     * @param NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier
     */
    private function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeToInsert $nodeToInsert, DimensionSpacePoint $dimensionSpacePoint, UserIdentifier $initiatingUserIdentifier, NodeAggregateIdentifier $targetParentNodeAggregateIdentifier, ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeToInsert = $nodeToInsert;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->targetParentNodeAggregateIdentifier = $targetParentNodeAggregateIdentifier;
        $this->targetSucceedingSiblingNodeAggregateIdentifier = $targetSucceedingSiblingNodeAggregateIdentifier;
    }

    public function create(TraversableNode $sourceNode, DimensionSpacePoint $dimensionSpacePoint, UserIdentifier $initiatingUserIdentifier, NodeAggregateIdentifier $targetParentNodeAggregateIdentifier, ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier)
    {
        return new static(
            $sourceNode->getContentStreamIdentifier(),
            NodeToInsert::fromTraversableNode($sourceNode)->withNodeName(NodeName::fromString(uniqid('node-'))),
            $dimensionSpacePoint,
            $initiatingUserIdentifier,
            $targetParentNodeAggregateIdentifier,
            $targetSucceedingSiblingNodeAggregateIdentifier
        );
    }


    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeToInsert::fromArray($array['nodeToInsert']),
            new DimensionSpacePoint($array['dimensionSpacePoint']),
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            NodeAggregateIdentifier::fromString($array['targetParentNodeAggregateIdentifier']),
            isset($array['targetSucceedingSiblingNodeAggregateIdentifier']) ? NodeAggregateIdentifier::fromString($array['targetSucceedingSiblingNodeAggregateIdentifier']) : null
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
     * @return NodeToInsert
     */
    public function getNodeToInsert(): NodeToInsert
    {
        return $this->nodeToInsert;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
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
            'dimensionSpacePoint' => $this->dimensionSpacePoint,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'targetParentNodeAggregateIdentifier' => $this->targetParentNodeAggregateIdentifier,
            'targetSucceedingSiblingNodeAggregateIdentifier' => $this->targetSucceedingSiblingNodeAggregateIdentifier,
        ];
    }
}
