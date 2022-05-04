<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Projection\Content;

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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\CoverageByOrigin;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint;
/** @codingStandardsIgnoreEnd */

use Neos\ContentRepository\SharedModel\Node\OriginByCoverage;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use function Neos\EventSourcedContentRepository\Domain\Projection\Content\array_values;
use function Neos\EventSourcedContentRepository\Domain\Projection\Content\is_null;

/**
 * Node aggregate read model
 */
final class NodeAggregate implements ReadableNodeAggregateInterface
{
    public function __construct(
        private ContentStreamIdentifier $contentStreamIdentifier,
        private NodeAggregateIdentifier $nodeAggregateIdentifier,
        private NodeAggregateClassification $classification,
        private NodeTypeName $nodeTypeName,
        private ?NodeName $nodeName,
        private OriginDimensionSpacePointSet $occupiedDimensionSpacePoints,
        /** @var array<string,NodeInterface> */
        private array $nodesByOccupiedDimensionSpacePoint,
        private CoverageByOrigin $coverageByOccupant,
        private DimensionSpacePointSet $coveredDimensionSpacePoints,
        /** @var array<string,NodeInterface> */
        private array $nodesByCoveredDimensionSpacePoint,
        private OriginByCoverage $occupationByCovered,
        /**
         * The dimension space point set this node aggregate disables.
         * This is *not* necessarily the set it is disabled in, since that is determined by its ancestors
         */
        private DimensionSpacePointSet $disabledDimensionSpacePoints
    ) {
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function getOccupiedDimensionSpacePoints(): OriginDimensionSpacePointSet
    {
        return $this->occupiedDimensionSpacePoints;
    }

    public function occupiesDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): bool
    {
        return $this->occupiedDimensionSpacePoints->contains($originDimensionSpacePoint);
    }

    /**
     * Returns the nodes belonging to this aggregate, i.e. the "real materialized" node rows.
     *
     * @return iterable<int,NodeInterface>
     */
    public function getNodes(): iterable
    {
        return array_values($this->nodesByOccupiedDimensionSpacePoint);
    }

    public function getNodeByOccupiedDimensionSpacePoint(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): NodeInterface {
        if (!$this->occupiedDimensionSpacePoints->contains($occupiedDimensionSpacePoint)) {
            throw NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateIdentifier,
                $occupiedDimensionSpacePoint
            );
        }

        return $this->nodesByOccupiedDimensionSpacePoint[$occupiedDimensionSpacePoint->hash];
    }

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->coveredDimensionSpacePoints;
    }

    public function coversDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->coveredDimensionSpacePoints->contains($dimensionSpacePoint);
    }

    public function getCoverageByOccupant(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): DimensionSpacePointSet {
        $coverage = $this->coverageByOccupant->getCoverage($occupiedDimensionSpacePoint);
        if (is_null($coverage)) {
            throw NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateIdentifier,
                $occupiedDimensionSpacePoint
            );
        }

        return $coverage;
    }

    /**
     * @return array<string,NodeInterface>
     */
    public function getNodesByCoveredDimensionSpacePoint(): array
    {
        return $this->nodesByCoveredDimensionSpacePoint;
    }

    public function getNodeByCoveredDimensionSpacePoint(DimensionSpacePoint $coveredDimensionSpacePoint): NodeInterface
    {
        if (!isset($this->coveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash])) {
            throw NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateIdentifier,
                $coveredDimensionSpacePoint
            );
        }

        return $this->nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash];
    }

    public function getOccupationByCovered(DimensionSpacePoint $coveredDimensionSpacePoint): OriginDimensionSpacePoint
    {
        $occupation = $this->occupationByCovered->getOrigin($coveredDimensionSpacePoint);
        if (is_null($occupation)) {
            throw NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateIdentifier,
                $coveredDimensionSpacePoint
            );
        }

        return $occupation;
    }

    public function getDisabledDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->disabledDimensionSpacePoints;
    }

    public function disablesDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->disabledDimensionSpacePoints->contains($dimensionSpacePoint);
    }

    public function getClassification(): NodeAggregateClassification
    {
        return $this->classification;
    }

    public function isRoot(): bool
    {
        return $this->classification->isRoot();
    }

    public function isTethered(): bool
    {
        return $this->classification->isTethered();
    }
}
