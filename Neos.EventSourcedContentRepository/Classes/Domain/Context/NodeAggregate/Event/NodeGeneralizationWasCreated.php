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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A node generalization was created
 *
 * @Flow\Proxy(false)
 */
final class NodeGeneralizationWasCreated implements DomainEventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    private $sourceDimensionSpacePoint;

    /**
     * @var DimensionSpacePoint
     */
    private $generalizationLocation;

    /**
     * @var DimensionSpacePointSet
     */
    private $generalizationVisibility;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $sourceDimensionSpacePoint
     * @param DimensionSpacePoint $generalizationLocation
     * @param DimensionSpacePointSet $generalizationVisibility
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        DimensionSpacePoint $generalizationLocation,
        DimensionSpacePointSet $generalizationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->generalizationLocation = $generalizationLocation;
        $this->generalizationVisibility = $generalizationVisibility;
    }


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getSourceDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->sourceDimensionSpacePoint;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getGeneralizationLocation(): DimensionSpacePoint
    {
        return $this->generalizationLocation;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getGeneralizationVisibility(): DimensionSpacePointSet
    {
        return $this->generalizationVisibility;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodeGeneralizationWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodeGeneralizationWasCreated
    {
        return new NodeGeneralizationWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->sourceDimensionSpacePoint,
            $this->generalizationLocation,
            $this->generalizationVisibility
        );
    }
}
