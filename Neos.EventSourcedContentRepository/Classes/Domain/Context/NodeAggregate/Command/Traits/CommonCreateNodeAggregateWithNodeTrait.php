<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Traits;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * Helper which contains common fields for {@see CreateNodeAggregateWithNodeAndSerializedProperties}
 * and other derived commands
 */
trait CommonCreateNodeAggregateWithNodeTrait
{
    /**
     * The identifier of the content stream this command is to be handled in
     */
    private ContentStreamIdentifier $contentStreamIdentifier;

    /**
     * The new node's node aggregate identifier
     */
    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    /**
     * Name of the new node's type
     */
    private NodeTypeName $nodeTypeName;

    /**
     * Origin of the new node in the dimension space.
     * Will also be used to calculate a set of dimension points where the new node will cover
     * from the configured specializations.
     */
    private OriginDimensionSpacePoint $originDimensionSpacePoint;

    /**
     * The initiating user's identifier
     */
    private UserIdentifier $initiatingUserIdentifier;

    /**
     * The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     */
    private ?NodeName $nodeName;

    /**
     * Node aggregate identifier of the node's parent
     */
    private NodeAggregateIdentifier $parentNodeAggregateIdentifier;

    /**
     * Node aggregate identifier of the node's succeeding sibling (optional)
     *
     * If not given, the node will be added as the parent's first child
     */
    private ?NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier;

    /**
     * NodeAggregateIdentifiers for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate identifiers
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     */
    private ?NodeAggregateIdentifiersByNodePaths $tetheredDescendantNodeAggregateIdentifiers;

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeAggregateIdentifier;
    }

    public function getSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->succeedingSiblingNodeAggregateIdentifier;
    }

    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function getTetheredDescendantNodeAggregateIdentifiers(): ?NodeAggregateIdentifiersByNodePaths
    {
        return $this->tetheredDescendantNodeAggregateIdentifiers;
    }
}
