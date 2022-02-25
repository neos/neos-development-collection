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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CoverageByOrigin;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
/** @codingStandardsIgnoreStart */
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint;
/** @codingStandardsIgnoreEnd */
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;

/**
 * Node aggregate read model
 */
final class NodeAggregate implements ReadableNodeAggregateInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    private NodeAggregateClassification $classification;

    private NodeTypeName $nodeTypeName;

    private ?NodeName $nodeName;

    private OriginDimensionSpacePointSet $occupiedDimensionSpacePoints;

    /**
     * @var array<string,NodeInterface>
     */
    private array $nodesByOccupiedDimensionSpacePoint;

    private CoverageByOrigin $coverageByOccupant;

    /**
     * @var array<string,NodeInterface>
     */
    private array $nodesByCoveredDimensionSpacePoint;

    private DimensionSpacePointSet $coveredDimensionSpacePoints;

    /**
     * This is not a dimension space point set since it is indexed by covered hash and not by member hash
     *
     * @var array<string,OriginDimensionSpacePoint>
     */
    private array $occupationByCovered;

    /**
     * The dimension space point set this node aggregate disables.
     * This is *not* necessarily the set it is disabled in, since that is determined by its ancestors
     */
    private DimensionSpacePointSet $disabledDimensionSpacePoints;

    /**
     * @param array|NodeInterface[] $nodesByOccupiedDimensionSpacePoint
     * @param array|NodeInterface[] $nodesByCoveredDimensionSpacePoint
     * @param array<string,OriginDimensionSpacePoint> $occupationByCovered
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeAggregateClassification $classification,
        NodeTypeName $nodeTypeName,
        ?NodeName $nodeName,
        OriginDimensionSpacePointSet $occupiedDimensionSpacePoints,
        array $nodesByOccupiedDimensionSpacePoint,
        CoverageByOrigin $coverageByOccupant,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        array $nodesByCoveredDimensionSpacePoint,
        array $occupationByCovered,
        DimensionSpacePointSet $disabledDimensionSpacePoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->classification = $classification;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeName = $nodeName;
        $this->occupiedDimensionSpacePoints = $occupiedDimensionSpacePoints;
        $this->nodesByOccupiedDimensionSpacePoint = $nodesByOccupiedDimensionSpacePoint;
        $this->coverageByOccupant = $coverageByOccupant;
        $this->coveredDimensionSpacePoints = $coveredDimensionSpacePoints;
        $this->nodesByCoveredDimensionSpacePoint = $nodesByCoveredDimensionSpacePoint;
        $this->occupationByCovered = $occupationByCovered;
        $this->disabledDimensionSpacePoints = $disabledDimensionSpacePoints;
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
     * @return NodeInterface[]
     */
    public function getNodes(): iterable
    {
        return array_values($this->nodesByOccupiedDimensionSpacePoint);
    }

    public function getNodeByOccupiedDimensionSpacePoint(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): NodeInterface {
        if (!$this->occupiedDimensionSpacePoints->contains($occupiedDimensionSpacePoint)) {
            throw new NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint(
                'Node aggregate "' . $this->nodeAggregateIdentifier
                    . '" does currently not occupy dimension space point ' . $occupiedDimensionSpacePoint,
                1554902613
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
        return $this->coverageByOccupant->getCoverage($occupiedDimensionSpacePoint);
    }

    /**
     * @return array|NodeInterface[]
     */
    public function getNodesByCoveredDimensionSpacePoint(): array
    {
        return $this->nodesByCoveredDimensionSpacePoint;
    }

    public function getNodeByCoveredDimensionSpacePoint(DimensionSpacePoint $coveredDimensionSpacePoint): NodeInterface
    {
        if (!isset($this->coveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash])) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint(
                'Node aggregate "' . $this->nodeAggregateIdentifier
                    . '" does currently not cover dimension space point '
                    . $coveredDimensionSpacePoint,
                1554902892
            );
        }

        return $this->nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash];
    }

    public function getOccupationByCovered(DimensionSpacePoint $coveredDimensionSpacePoint): OriginDimensionSpacePoint
    {
        if (!isset($this->occupationByCovered[$coveredDimensionSpacePoint->hash])) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint(
                'Node aggregate "' . $this->nodeAggregateIdentifier
                    . '" does currently not cover dimension space point ' . $coveredDimensionSpacePoint,
                1554902892
            );
        }

        return $this->occupationByCovered[$coveredDimensionSpacePoint->hash];
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
