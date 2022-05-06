<?php
declare(strict_types=1);
namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

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
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\PropertyCollectionInterface;

/**
 * The "new" Event-Sourced Node. Does NOT contain tree traversal logic; this is implemented in TraversableNode.
 */
final class Node implements NodeInterface
{
    public function __construct(
        private ContentStreamIdentifier $contentStreamIdentifier,
        private NodeAggregateIdentifier $nodeAggregateIdentifier,
        private OriginDimensionSpacePoint $originDimensionSpacePoint,
        private NodeTypeName $nodeTypeName,
        private NodeType $nodeType,
        private ?NodeName $nodeName,
        /** @var PropertyCollectionInterface<string,mixed> $properties */
        private PropertyCollectionInterface $properties,
        private NodeAggregateClassification $classification,
        private DimensionSpacePoint $dimensionSpacePoint,
        private VisibilityConstraints $visibilityConstraints
    ) {
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

    /**
     * @return PropertyCollectionInterface<string,mixed>
     */
    public function getProperties(): PropertyCollectionInterface
    {
        return $this->properties;
    }

    /**
     * Returns the specified property.
     *
     * @param string $propertyName
     * @api
     */
    public function getProperty($propertyName): mixed
    {
        return $this->properties[$propertyName];
    }

    public function hasProperty($propertyName): bool
    {
        return $this->properties->offsetExists($propertyName);
    }

    public function getLabel(): string
    {
        return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this);
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return $this->visibilityConstraints;
    }

    public function equals(NodeInterface $other): bool
    {
        return $this->getContentStreamIdentifier() === $other->getContentStreamIdentifier()
            && $this->getDimensionSpacePoint() === $other->getDimensionSpacePoint()
            && $this->getNodeAggregateIdentifier()->equals($other->getNodeAggregateIdentifier());
    }
}
