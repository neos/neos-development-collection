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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A node generalization variant was created
 *
 * @Flow\Proxy(false)
 */
final class NodeGeneralizationVariantWasCreated implements DomainEventInterface, CopyableAcrossContentStreamsInterface
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
     * @var OriginDimensionSpacePoint
     */
    private $sourceOrigin;

    /**
     * @var OriginDimensionSpacePoint
     */
    private $generalizationOrigin;

    /**
     * @var DimensionSpacePointSet
     */
    private $generalizationCoverage;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $generalizationOrigin,
        DimensionSpacePointSet $generalizationCoverage
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceOrigin = $sourceOrigin;
        $this->generalizationOrigin = $generalizationOrigin;
        $this->generalizationCoverage = $generalizationCoverage;
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
     * @return OriginDimensionSpacePoint
     */
    public function getSourceOrigin(): OriginDimensionSpacePoint
    {
        return $this->sourceOrigin;
    }

    /**
     * @return OriginDimensionSpacePoint
     */
    public function getGeneralizationOrigin(): OriginDimensionSpacePoint
    {
        return $this->generalizationOrigin;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getGeneralizationCoverage(): DimensionSpacePointSet
    {
        return $this->generalizationCoverage;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStreamIdentifier
     * @return NodeGeneralizationVariantWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): NodeGeneralizationVariantWasCreated
    {
        return new NodeGeneralizationVariantWasCreated(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->sourceOrigin,
            $this->generalizationOrigin,
            $this->generalizationCoverage
        );
    }
}
