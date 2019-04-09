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
     * @var DimensionSpacePoint
     */
    private $sourceOrigin;

    /**
     * @var DimensionSpacePoint
     */
    private $generalizationOrigin;

    /**
     * @var DimensionSpacePointSet
     */
    private $generalizationCoverage;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceOrigin,
        DimensionSpacePoint $generalizationOrigin,
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
     * @return DimensionSpacePoint
     */
    public function getSourceOrigin(): DimensionSpacePoint
    {
        return $this->sourceOrigin;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getGeneralizationOrigin(): DimensionSpacePoint
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
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodeGeneralizationVariantWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodeGeneralizationVariantWasCreated
    {
        return new NodeGeneralizationVariantWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->sourceOrigin,
            $this->generalizationOrigin,
            $this->generalizationCoverage
        );
    }
}
