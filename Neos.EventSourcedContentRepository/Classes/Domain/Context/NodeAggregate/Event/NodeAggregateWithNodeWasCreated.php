<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * A node aggregate with its initial node was created
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateWithNodeWasCreated implements DomainEventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * The content stream identifier the node aggregate and its node were created in
     *
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * The origin dimension space point the node aggregate and its node were created in
     *
     * @var DimensionSpacePoint
     */
    private $originDimensionSpacePoint;

    /**
     * The dimension space points the node aggregate and its node are visible in
     *
     * @var DimensionSpacePointSet
     */
    private $visibleInDimensionSpacePoints;

    /**
     * The node aggregate's identifier
     *
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * The node aggregate type's name
     *
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * The parent node aggregate's identifier
     *
     * @var NodeAggregateIdentifier
     */
    private $parentNodeAggregateIdentifier;

    /**
     * The node aggregate's name
     *
     * @var NodeName
     */
    private $nodeName;

    /**
     * The node's initial property values
     *
     * @var PropertyValues
     */
    private $initialPropertyValues;

    /**
     * The node's succeeding sibling's node aggregate identifier
     *
     * @var NodeAggregateIdentifier
     */
    private $succeedingNodeAggregateIdentifier;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @param DimensionSpacePointSet $visibleInDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $nodeName
     * @param PropertyValues $initialPropertyValues
     * @param NodeAggregateIdentifier|null $succeedingNodeAggregateIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $visibleInDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeName $nodeName,
        PropertyValues $initialPropertyValues,
        NodeAggregateIdentifier $succeedingNodeAggregateIdentifier = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->visibleInDimensionSpacePoints = $visibleInDimensionSpacePoints;
        $this->parentNodeAggregateIdentifier = $parentNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues;
        $this->succeedingNodeAggregateIdentifier = $succeedingNodeAggregateIdentifier;
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

    public function getOriginDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function getVisibleInDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->visibleInDimensionSpacePoints;
    }

    public function getParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeAggregateIdentifier;
    }

    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function getInitialPropertyValues(): PropertyValues
    {
        return $this->initialPropertyValues;
    }

    public function getSucceedingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->succeedingNodeAggregateIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodeAggregateWithNodeWasCreated
    {
        return new NodeAggregateWithNodeWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->visibleInDimensionSpacePoints,
            $this->parentNodeAggregateIdentifier,
            $this->nodeName,
            $this->initialPropertyValues,
            $this->succeedingNodeAggregateIdentifier
        );
    }
}
