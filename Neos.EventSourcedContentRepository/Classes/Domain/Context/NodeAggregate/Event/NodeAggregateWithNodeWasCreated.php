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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * A node aggregate with its initial node was created
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateWithNodeWasCreated implements DomainEventInterface, PublishableToOtherContentStreamsInterface
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
     * @var OriginDimensionSpacePoint
     */
    private $originDimensionSpacePoint;

    /**
     * The dimension space points the node aggregate and its node cover
     *
     * @var DimensionSpacePointSet
     */
    private $coveredDimensionSpacePoints;

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
     * @var SerializedPropertyValues
     */
    private $initialPropertyValues;

    /**
     * The node aggregate's classification
     *
     * @var NodeAggregateClassification
     */
    private $nodeAggregateClassification;

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
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $nodeName
     * @param SerializedPropertyValues $initialPropertyValues
     * @param NodeAggregateClassification $nodeAggregateClassification
     * @param NodeAggregateIdentifier|null $succeedingNodeAggregateIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeName $nodeName,
        SerializedPropertyValues $initialPropertyValues,
        NodeAggregateClassification $nodeAggregateClassification,
        NodeAggregateIdentifier $succeedingNodeAggregateIdentifier = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->coveredDimensionSpacePoints = $coveredDimensionSpacePoints;
        $this->parentNodeAggregateIdentifier = $parentNodeAggregateIdentifier;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues;
        $this->nodeAggregateClassification = $nodeAggregateClassification;
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

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->coveredDimensionSpacePoints;
    }

    public function getParentNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeAggregateIdentifier;
    }

    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function getInitialPropertyValues(): SerializedPropertyValues
    {
        return $this->initialPropertyValues;
    }

    public function getNodeAggregateClassification(): NodeAggregateClassification
    {
        return $this->nodeAggregateClassification;
    }

    public function getSucceedingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->succeedingNodeAggregateIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): NodeAggregateWithNodeWasCreated
    {
        return new NodeAggregateWithNodeWasCreated(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->coveredDimensionSpacePoints,
            $this->parentNodeAggregateIdentifier,
            $this->nodeName,
            $this->initialPropertyValues,
            $this->nodeAggregateClassification,
            $this->succeedingNodeAggregateIdentifier
        );
    }
}
