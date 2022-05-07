<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Fixtures;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\PropertyCollection;
use Neos\ContentRepository\Projection\Content\PropertyCollectionInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;

/**
 * A valid read model implementation
 */
final class Thing implements NodeInterface
{
    protected ContentStreamIdentifier $contentStreamIdentifier;

    protected NodeAggregateIdentifier $nodeAggregateIdentifier;

    protected OriginDimensionSpacePoint $originDimensionSpacePoint;

    protected NodeTypeName $nodeTypeName;

    protected NodeType $nodeType;

    protected ?NodeName $nodeName;

    protected SerializedPropertyValues $serializedProperties;

    protected NodeAggregateClassification $classification;

    protected PropertyCollection $properties;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeTypeName $nodeTypeName,
        NodeType $nodeType,
        ?NodeName $nodeName,
        PropertyCollection $propertyCollection,
        NodeAggregateClassification $classification
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeType = $nodeType;
        $this->nodeName = $nodeName;
        $this->properties = $propertyCollection;
        $this->classification = $classification;
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
     * @api
     */
    public function getProperty($propertyName)
    {
        return $this->properties[$propertyName];
    }

    public function hasProperty($propertyName): bool
    {
        return $this->serializedProperties->propertyExists($propertyName);
    }



    public function getCacheEntryIdentifier(): string
    {
        throw new \RuntimeException('Not supported');
    }

    public function getLabel(): string
    {
        throw new \RuntimeException('Not supported');
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        // TODO!!!
        return $this->dimensionSpacePoint;
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
    }

    public function getClassification(): NodeAggregateClassification
    {
        // TODO: Implement getClassification() method.
    }

    public function equals(NodeInterface $other): bool
    {
        return false;
    }
}
