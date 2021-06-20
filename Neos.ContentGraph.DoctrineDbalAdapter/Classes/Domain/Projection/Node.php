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

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * The "new" Event-Sourced Node. Does NOT contain tree traversal logic; this is implemented in TraversableNode.
 */
final class Node implements NodeInterface
{
    protected ContentStreamIdentifier $contentStreamIdentifier;

    protected NodeAggregateIdentifier $nodeAggregateIdentifier;

    protected OriginDimensionSpacePoint $originDimensionSpacePoint;

    protected NodeTypeName $nodeTypeName;

    protected NodeType $nodeType;

    protected ?NodeName $nodeName;

    protected SerializedPropertyValues $properties;

    protected NodeAggregateClassification $classification;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeTypeName $nodeTypeName,
        NodeType $nodeType,
        ?NodeName $nodeName,
        SerializedPropertyValues $properties,
        NodeAggregateClassification $classification
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeType = $nodeType;
        $this->nodeName = $nodeName;
        $this->properties = $properties;
        $this->classification = $classification;
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

    public function getProperties(): SerializedPropertyValues
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
    public function getProperty(string $propertyName)
    {
        return $this->properties->getProperty($propertyName)->getValue();
    }

    public function hasProperty($propertyName): bool
    {
        return $this->properties->propertyExists($propertyName);
    }
}
