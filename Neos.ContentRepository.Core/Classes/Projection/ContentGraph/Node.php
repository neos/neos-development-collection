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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * Main read model of the {@see ContentSubgraphInterface}.
 *
 * Immutable, Read Only. In case you want to modify it, you need
 * to create Commands and send them to ContentRepository::handle.
 *
 * The node does not have structure information, i.e. no infos
 * about its children. To f.e. fetch children, you need to fetch
 * the subgraph {@see ContentGraphInterface::getSubgraph()} via
 * $subgraphIdentity {@see Node::$subgraphIdentity}. and then
 * call findChildNodes() {@see ContentSubgraphInterface::findChildNodes()}
 * on the subgraph.
 *
 * @api Note: The constructor is not part of the public API
 */
final readonly class Node
{
    /**
     * @param ContentSubgraphIdentity $subgraphIdentity This is part of the node's "Read Model" identity which is defined by: {@see self::subgraphIdentity} and {@see self::nodeAggregateId}. With this information, you can fetch a Subgraph using {@see ContentGraphInterface::getSubgraph()}.
     * @param NodeAggregateId $nodeAggregateId NodeAggregateId (identifier) of this node. This is part of the node's "Read Model" identity which is defined by: {@see self::subgraphIdentity} and {@see self::nodeAggregateId}
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint The DimensionSpacePoint the node originates in. Usually needed to address a Node in a NodeAggregate in order to update it.
     * @param NodeAggregateClassification $classification The classification (regular, root, tethered) of this node
     * @param NodeTypeName $nodeTypeName The node's node type name; always set, even if unknown to the NodeTypeManager
     * @param NodeType|null $nodeType The node's node type, null if unknown to the NodeTypeManager - @deprecated Don't rely on this too much, as the capabilities of the NodeType here will probably change a lot; Ask the {@see NodeTypeManager} instead
     * @param PropertyCollection $properties All properties of this node. References are NOT part of this API; To access references, {@see ContentSubgraphInterface::findReferences()} can be used; To read the serialized properties use {@see PropertyCollection::serialized()}.
     * @param NodeName|null $nodeName The optionally named hierarchy relation to the node's parent.
     * @param NodeTags $tags explicit and inherited SubtreeTags of this node
     * @param Timestamps $timestamps Creation and modification timestamps of this node
     */
    private function __construct(
        public ContentSubgraphIdentity $subgraphIdentity,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public NodeAggregateClassification $classification,
        public NodeTypeName $nodeTypeName,
        public ?NodeType $nodeType,
        public PropertyCollection $properties,
        public ?NodeName $nodeName,
        public NodeTags $tags,
        public Timestamps $timestamps,
    ) {
        if ($this->classification->isTethered() && $this->nodeName === null) {
            throw new \InvalidArgumentException('The NodeName must be set if the Node is tethered.', 1695118377);
        }
    }

    /**
     * @internal The signature of this method can change in the future!
     */
    public static function create(ContentSubgraphIdentity $subgraphIdentity, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateClassification $classification, NodeTypeName $nodeTypeName, ?NodeType $nodeType, PropertyCollection $properties, ?NodeName $nodeName, NodeTags $tags, Timestamps $timestamps): self
    {
        return new self($subgraphIdentity, $nodeAggregateId, $originDimensionSpacePoint, $classification, $nodeTypeName, $nodeType, $properties, $nodeName, $tags, $timestamps);
    }

    /**
     * Returns the specified property, or null if it does not exist (or was set to null -> unset)
     *
     * @param string $propertyName Name of the property
     * @return mixed value of the property
     * @api
     */
    public function getProperty(string $propertyName): mixed
    {
        return $this->properties->offsetGet($propertyName);
    }

    /**
     * If this node has a property with the given name. It does not check if the property exists in the current NodeType schema.
     *
     * That means that {@see self::getProperty()} will not be null, except for the rare case the property deserializing returns null.
     *
     * @param string $propertyName Name of the property
     * @return boolean
     * @api
     */
    public function hasProperty(string $propertyName): bool
    {
        return $this->properties->offsetExists($propertyName);
    }

    /**
     * Returns the node label as generated by the configured node label generator
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->nodeType?->getNodeLabelGenerator()->getLabel($this) ?: $this->nodeTypeName->value;
    }

    public function equals(Node $other): bool
    {
        return $this->subgraphIdentity->equals($other->subgraphIdentity)
            && $this->nodeAggregateId->equals($other->nodeAggregateId);
    }
}
