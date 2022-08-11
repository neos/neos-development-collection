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

namespace Neos\ContentRepository\Feature\NodeCreation\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\NodeAddress;

/**
 * CreateNodeAggregateWithNode command
 */
#[Flow\Proxy(false)]
final class CreateNodeAggregateWithNodeAndSerializedProperties implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface
{
    /**
     * The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     */
    public readonly ?NodeName $nodeName;

    /**
     * Node aggregate identifier of the node's succeeding sibling (optional)
     * If not given, the node will be added as the parent's first child
     */
    public readonly ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     */
    public readonly SerializedPropertyValues $initialPropertyValues;

    /**
     * NodeAggregateIdentifiers for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate identifiers
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     */
    public readonly NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers;

    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeTypeName $nodeTypeName,
        /**
         * Origin of the new node in the dimension space.
         * Will also be used to calculate a set of dimension points where the new node will cover
         * from the configured specializations.
         */
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly UserIdentifier $initiatingUserIdentifier,
        public readonly NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null,
        ?NodeName $nodeName = null,
        ?SerializedPropertyValues $initialPropertyValues = null,
        ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers = null
    ) {
        $this->succeedingSiblingNodeAggregateIdentifier = $succeedingSiblingNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues ?: SerializedPropertyValues::fromArray([]);
        $this->tetheredDescendantNodeAggregateIdentifiers = $tetheredDescendantNodeAggregateIdentifiers
            ?: new NodeAggregateIdentifiersByNodePaths([]);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['nodeTypeName']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            NodeAggregateIdentifier::fromString($array['parentNodeAggregateIdentifier']),
            isset($array['succeedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($array['succeedingSiblingNodeAggregateIdentifier'])
                : null,
            isset($array['nodeName'])
                ? NodeName::fromString($array['nodeName'])
                : null,
            isset($array['initialPropertyValues'])
                ? SerializedPropertyValues::fromArray($array['initialPropertyValues'])
                : null,
            isset($array['tetheredDescendantNodeAggregateIdentifiers'])
                ? NodeAggregateIdentifiersByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIdentifiers'])
                : null
        );
    }

    public function getInitialPropertyValues(): SerializedPropertyValues
    {
        return $this->initialPropertyValues;
    }

    /**
     * Create a new CreateNodeAggregateWithNode command with all original values,
     * except the tetheredDescendantNodeAggregateIdentifiers (where the passed in arguments are used).
     *
     * Is needed to make this command fully deterministic before storing it at the events
     * - we need this
     */
    public function withTetheredDescendantNodeAggregateIdentifiers(
        NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers
    ): self {
        return new self(
            $this->contentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->initiatingUserIdentifier,
            $this->parentNodeAggregateIdentifier,
            $this->succeedingSiblingNodeAggregateIdentifier,
            $this->nodeName,
            $this->initialPropertyValues,
            $tetheredDescendantNodeAggregateIdentifiers
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
            'nodeTypeName' => $this->nodeTypeName,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'parentNodeAggregateIdentifier' => $this->parentNodeAggregateIdentifier,
            'succeedingSiblingNodeAggregateIdentifier' => $this->succeedingSiblingNodeAggregateIdentifier,
            'nodeName' => $this->nodeName,
            'initialPropertyValues' => $this->initialPropertyValues,
            'tetheredDescendantNodeAggregateIdentifiers' => $this->tetheredDescendantNodeAggregateIdentifiers
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->initiatingUserIdentifier,
            $this->parentNodeAggregateIdentifier,
            $this->succeedingSiblingNodeAggregateIdentifier,
            $this->nodeName,
            $this->initialPropertyValues,
            $this->tetheredDescendantNodeAggregateIdentifiers
        );
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        return (
            $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
                && $this->nodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier)
                && $this->originDimensionSpacePoint->equals($nodeIdentifierToPublish->dimensionSpacePoint)
        );
    }
}
