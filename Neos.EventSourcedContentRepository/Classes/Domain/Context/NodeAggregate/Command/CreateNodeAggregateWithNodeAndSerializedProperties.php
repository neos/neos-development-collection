<?php

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

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Traits\CommonCreateNodeAggregateWithNodeTrait;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * CreateNodeAggregateWithNode command
 *
 * @Flow\Proxy(false)
 */
final class CreateNodeAggregateWithNodeAndSerializedProperties implements \JsonSerializable, RebasableToOtherContentStreamsInterface, MatchableWithNodeAddressInterface
{
    use CommonCreateNodeAggregateWithNodeTrait;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     */
    private SerializedPropertyValues $initialPropertyValues;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        UserIdentifier $initiatingUserIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null,
        ?NodeName $nodeName = null,
        ?SerializedPropertyValues $initialPropertyValues = null,
        ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->parentNodeAggregateIdentifier = $parentNodeAggregateIdentifier;
        $this->succeedingSiblingNodeAggregateIdentifier = $succeedingSiblingNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues ?: SerializedPropertyValues::fromArray([]);
        $this->tetheredDescendantNodeAggregateIdentifiers = $tetheredDescendantNodeAggregateIdentifiers ?: new NodeAggregateIdentifiersByNodePaths([]);
    }

    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['nodeTypeName']),
            OriginDimensionSpacePoint::instance($array['originDimensionSpacePoint']),
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
     * Create a new CreateNodeAggregateWithNode command with all original values, except the tetheredDescendantNodeAggregateIdentifiers (where
     * the passed in arguments are used).
     *
     * Is needed to make this command fully deterministic before storing it at the events
     * - we need this
     * @param NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers
     * @return self
     */
    public function withTetheredDescendantNodeAggregateIdentifiers(NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers): self
    {
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

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
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

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->contentStreamIdentifier === (string)$nodeAddress->getContentStreamIdentifier()
            && (string)$this->nodeAggregateIdentifier === (string)$nodeAddress->getNodeAggregateIdentifier()
            && $this->originDimensionSpacePoint->equals(OriginDimensionSpacePoint::fromDimensionSpacePoint($nodeAddress->getDimensionSpacePoint()))
        );
    }
}
