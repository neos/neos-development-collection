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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class NodePeerVariantWasCreated implements DomainEventInterface, CopyableAcrossContentStreamsInterface
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
    private $alternativeLocation;

    /**
     * @var DimensionSpacePointSet
     */
    private $alternativeVisibility;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $sourceDimensionSpacePoint
     * @param DimensionSpacePoint $alternativeLocation
     * @param DimensionSpacePointSet $alternativeVisibility
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        DimensionSpacePoint $alternativeLocation,
        DimensionSpacePointSet $alternativeVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->alternativeLocation = $alternativeLocation;
        $this->alternativeVisibility = $alternativeVisibility;
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
    public function getAlternativeLocation(): DimensionSpacePoint
    {
        return $this->alternativeLocation;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getAlternativeVisibility(): DimensionSpacePointSet
    {
        return $this->alternativeVisibility;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodePeerVariantWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodePeerVariantWasCreated
    {
        return new NodePeerVariantWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->sourceDimensionSpacePoint,
            $this->alternativeLocation,
            $this->alternativeVisibility
        );
    }
}
