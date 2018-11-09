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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;

/**
 * CreateNodeAggregateWithNode command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateIdentifier` and `nodeIdentifier`.
 * The node will be appended as child node of the given `parentNodeIdentifier` which must be visible in the given
 * `dimensionSpacePoint`.
 */
final class CreateNodeAggregateWithNode
{
    /**
     * The identifier of the content stream this command is to be handled in
     *
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * The new node's node aggregate identifier
     *
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * Name of the new node's type
     *
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * Origin of the new node in the dimension space.
     * Will also be used to calculate a set of dimension points where the new node will be visible in
     * from the configured specializations.
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * The node's name
     *
     * @var NodeName
     */
    private $nodeName;

    /**
     * Node aggregate identifier of the node's parent (optional)
     *
     * If not given, the node will be added as a root node
     *
     * @var NodeAggregateIdentifier
     */
    private $parentNodeAggregateIdentifier;

    /**
     * Node aggregate identifier of the node's succeeding sibling (optional)
     *
     * If not given, the node will be added as the parent's first child
     *
     * @var NodeAggregateIdentifier
     */
    private $succeedingSiblingNodeAggregateIdentifier;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     *
     * @var PropertyValues
     */
    private $initialPropertyValues;

    /**
     * NodeAggregateIdentifiers for auto created descendants (optional).
     *
     * If the given node type has auto created child nodes, you may predefine their node aggregate identifiers
     * using this assignment registry.
     * Since auto created child nodes may have auto created child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     *
     * @var NodeAggregateIdentifiersByNodePaths
     */
    private $autoCreatedDescendantNodeAggregateIdentifiers;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null,
        PropertyValues $initialPropertyValues = null,
        NodeAggregateIdentifiersByNodePaths $autoCreatedDescendantNodeAggregateIdentifiers = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->parentNodeAggregateIdentifier = $parentNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->succeedingSiblingNodeAggregateIdentifier = $succeedingSiblingNodeAggregateIdentifier;
        $this->initialPropertyValues = $initialPropertyValues ?: new PropertyValues([]);
        $this->autoCreatedDescendantNodeAggregateIdentifiers = $autoCreatedDescendantNodeAggregateIdentifiers ?: new NodeAggregateIdentifiersByNodePaths([]);
    }

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

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeAggregateIdentifier;
    }

    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    public function getSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->succeedingSiblingNodeAggregateIdentifier;
    }

    public function getInitialPropertyValues(): PropertyValues
    {
        return $this->initialPropertyValues;
    }

    public function getAutoCreatedDescendantNodeAggregateIdentifiers(): NodeAggregateIdentifiersByNodePaths
    {
        return $this->autoCreatedDescendantNodeAggregateIdentifiers;
    }
}
