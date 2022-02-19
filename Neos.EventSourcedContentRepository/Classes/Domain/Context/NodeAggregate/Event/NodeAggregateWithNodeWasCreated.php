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
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * A node aggregate with its initial node was created
 */
#[Flow\Proxy(false)]
final class NodeAggregateWithNodeWasCreated implements DomainEventInterface, PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    /**
     * The content stream identifier the node aggregate and its node were created in
     */
    private ContentStreamIdentifier $contentStreamIdentifier;

    /**
     * The origin dimension space point the node aggregate and its node were created in
     */
    private OriginDimensionSpacePoint $originDimensionSpacePoint;

    /**
     * The dimension space points the node aggregate and its node cover
     */
    private DimensionSpacePointSet $coveredDimensionSpacePoints;

    /**
     * The node aggregate's identifier
     */
    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    /**
     * The node aggregate type's name
     */
    private NodeTypeName $nodeTypeName;

    /**
     * The parent node aggregate's identifier
     */
    private NodeAggregateIdentifier $parentNodeAggregateIdentifier;

    /**
     * The node aggregate's name
     */
    private ?NodeName $nodeName;

    /**
     * The node's initial property values
     */
    private SerializedPropertyValues $initialPropertyValues;

    /**
     * The node aggregate's classification
     */
    private NodeAggregateClassification $nodeAggregateClassification;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * The node's succeeding sibling's node aggregate identifier
     */
    private ?NodeAggregateIdentifier $succeedingNodeAggregateIdentifier;

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
        UserIdentifier $initiatingUserIdentifier,
        ?NodeAggregateIdentifier $succeedingNodeAggregateIdentifier = null
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
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
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

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getSucceedingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->succeedingNodeAggregateIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->coveredDimensionSpacePoints,
            $this->parentNodeAggregateIdentifier,
            $this->nodeName,
            $this->initialPropertyValues,
            $this->nodeAggregateClassification,
            $this->initiatingUserIdentifier,
            $this->succeedingNodeAggregateIdentifier
        );
    }
}
