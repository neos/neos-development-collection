<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Content;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Projection\ContentGraph\PropertyCollectionInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The node implementation for the PostgreSQL content graph adapter
 *
 * @internal
 */
#[Flow\Proxy(false)]
final class Node implements NodeInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    private OriginDimensionSpacePoint $originDimensionSpacePoint;

    private NodeTypeName $nodeTypeName;

    private NodeType $nodeType;

    private ?NodeName $nodeName;

    private PropertyCollectionInterface $properties;

    private NodeAggregateClassification $classification;

    private DimensionSpacePoint $perspectiveDimensionSpacePoint;

    private VisibilityConstraints $perspectiveVisibilityConstraints;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeTypeName $nodeTypeName,
        NodeType $nodeType,
        ?NodeName $nodeName,
        PropertyCollectionInterface $properties,
        NodeAggregateClassification $classification,
        DimensionSpacePoint $perspectiveDimensionSpacePoint,
        VisibilityConstraints $perspectiveVisibilityConstraints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeType = $nodeType;
        $this->nodeName = $nodeName;
        $this->properties = $properties;
        $this->classification = $classification;
        $this->perspectiveDimensionSpacePoint = $perspectiveDimensionSpacePoint;
        $this->perspectiveVisibilityConstraints = $perspectiveVisibilityConstraints;
    }

    public function getClassification(): NodeAggregateClassification
    {
        return $this->classification;
    }

    /**
     * Whether or not this node is a root of the graph, i.e. has no parent node
     */
    public function isRoot(): bool
    {
        return $this->classification->isRoot();
    }

    /**
     * Whether or not this node is tethered to its parent, fka auto created child node
     */
    public function isTethered(): bool
    {
        return $this->classification->isTethered();
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getNodeType(): NodeType
    {
        return $this->nodeType;
    }

    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function getProperties(): PropertyCollectionInterface
    {
        return $this->properties;
    }

    /**
     * Returns the specified property.
     *
     * @param string $propertyName Name of the property
     * @return mixed value of the property
     */
    public function getProperty($propertyName)
    {
        return $this->properties->offsetGet($propertyName);
    }

    public function hasProperty($propertyName): bool
    {
        return $this->properties->offsetExists($propertyName);
    }

    /**
     * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
     * related to this object.
     *
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return sha1(json_encode([
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint
        ], JSON_THROW_ON_ERROR));
    }

    public function getLabel(): string
    {
        return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this);
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->perspectiveDimensionSpacePoint;
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return $this->perspectiveVisibilityConstraints;
    }

    public function equals(NodeInterface $other): bool
    {
        return $this->getContentStreamIdentifier()->equals($other->getContentStreamIdentifier())
            && $this->getDimensionSpacePoint()->equals($other->getDimensionSpacePoint())
            && $this->getNodeAggregateIdentifier()->equals($other->getNodeAggregateIdentifier());
    }
}
