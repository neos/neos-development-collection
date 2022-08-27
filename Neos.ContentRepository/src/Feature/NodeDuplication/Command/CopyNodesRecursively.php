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

namespace Neos\ContentRepository\Feature\NodeDuplication\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * CopyNodesRecursively command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateIdentifier` and `nodeIdentifier`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must cover the given
 * `dimensionSpacePoint`.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class CopyNodesRecursively implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface,
    RebasableToOtherContentStreamsInterface
{

    private function __construct(
        /**
         * The identifier of the content stream this command is to be handled in
         *
         * @var ContentStreamIdentifier
         */
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        /**
         * The to be copied node's node aggregate identifier
         *
         * @var NodeSubtreeSnapshot
         */
        public readonly NodeSubtreeSnapshot $nodeTreeToInsert,
        /**
         * the dimension space point which is the target of the copy
         *
         * @var OriginDimensionSpacePoint
         */
        public readonly OriginDimensionSpacePoint $targetDimensionSpacePoint,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * Node aggregate identifier of the target node's parent (optional)
         *
         * If not given, the node will be added as a root node
         *
         * @var NodeAggregateIdentifier
         */
        public readonly NodeAggregateIdentifier $targetParentNodeAggregateIdentifier,
        /**
         * Node aggregate identifier of the target node's succeeding sibling (optional)
         *
         * If not given, the node will be added as the parent's first child
         *
         * @var NodeAggregateIdentifier|null
         */
        public readonly ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier,
        /**
         * the root node name of the root-inserted-node
         *
         * @var NodeName|null
         */
        public readonly ?NodeName $targetNodeName,
        public readonly NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping
    ) {
    }

    /**
     * @todo (could be an extra method) reference start node by address instead of passing it
     */
    public static function createFromSubgraphAndStartNode(
        ContentSubgraphInterface $subgraph,
        Node $startNode,
        OriginDimensionSpacePoint $dimensionSpacePoint,
        UserIdentifier $initiatingUserIdentifier,
        NodeAggregateIdentifier $targetParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier,
        ?NodeName $targetNodeName
    ): self {
        $nodeSubtreeSnapshot = NodeSubtreeSnapshot::fromSubgraphAndStartNode($subgraph, $startNode);

        return new self(
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

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeSubtreeSnapshot::fromArray($array['nodeTreeToInsert']),
            OriginDimensionSpacePoint::fromArray($array['targetDimensionSpacePoint']),
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            NodeAggregateIdentifier::fromString($array['targetParentNodeAggregateIdentifier']),
            isset($array['targetSucceedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($array['targetSucceedingSiblingNodeAggregateIdentifier'])
                : null,
            isset($array['targetNodeName']) ? NodeName::fromString($array['targetNodeName']) : null,
            NodeAggregateIdentifierMapping::fromArray($array['nodeAggregateIdentifierMapping'])
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeTreeToInsert' => $this->nodeTreeToInsert,
            'targetDimensionSpacePoint' => $this->targetDimensionSpacePoint,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'targetParentNodeAggregateIdentifier' => $this->targetParentNodeAggregateIdentifier,
            'targetSucceedingSiblingNodeAggregateIdentifier' => $this->targetSucceedingSiblingNodeAggregateIdentifier,
            'targetNodeName' => $this->targetNodeName,
            'nodeAggregateIdentifierMapping' => $this->nodeAggregateIdentifierMapping,
        ];
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        $targetNodeAggregateIdentifier = $this->nodeAggregateIdentifierMapping->getNewNodeAggregateIdentifier(
            $this->nodeTreeToInsert->nodeAggregateIdentifier
        );
        return (
            !is_null($targetNodeAggregateIdentifier)
                && $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
                && $this->targetDimensionSpacePoint->equals($nodeIdentifierToPublish->dimensionSpacePoint)
                && $targetNodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier)
        );
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->initiatingUserIdentifier,
            $this->targetParentNodeAggregateIdentifier,
            $this->targetSucceedingSiblingNodeAggregateIdentifier,
            $this->targetNodeName,
            $this->nodeAggregateIdentifierMapping
        );
    }

    public function withNodeAggregateIdentifierMapping(
        NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping
    ): self {
        return new self(
            $this->contentStreamIdentifier,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->initiatingUserIdentifier,
            $this->targetParentNodeAggregateIdentifier,
            $this->targetSucceedingSiblingNodeAggregateIdentifier,
            $this->targetNodeName,
            $nodeAggregateIdentifierMapping
        );
    }
}
