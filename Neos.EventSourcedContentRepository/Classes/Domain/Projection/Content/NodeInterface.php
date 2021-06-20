<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * This is a NEW interface, introduced in Neos 4.3.
 *
 * The new Event-Sourced core NodeInterface used for READING. It contains only information
 * local to a node; i.e. all properties in this interface can be accessed extremely fast.
 *
 * The NodeInterface is *immutable*, meaning its contents never change after creation.
 * It is *only used for reading*.
 *
 * Starting with version 5.0 (when backed by the Event Sourced CR), it is
 * *completely detached from storage*; so it will not auto-update after a property changed in
 * storage.
 */
interface NodeInterface
{
    /**
     * Whether or not this node is a root of the graph, i.e. has no parent node
     */
    public function isRoot(): bool;

    /**
     * Whether or not this node is tethered to its parent, fka auto created child node
     */
    public function isTethered(): bool;

    public function getClassification(): NodeAggregateClassification;

    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier;

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint;

    public function getNodeTypeName(): NodeTypeName;

    public function getNodeType(): NodeType;

    public function getNodeName(): ?NodeName;

    public function getProperties(): SerializedPropertyValues;

    /**
     * Returns the specified property.
     *
     * @param string $propertyName Name of the property
     * @return mixed value of the property
     * @api
     */
    public function getProperty(string $propertyName);

    public function hasProperty($propertyName): bool;
}
