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
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint;
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
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeAggregateClassification
     */
    private $classification;

    /**
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var OriginDimensionSpacePointSet
     */
    private $occupiedDimensionSpacePoints;

    /**
     * @var array|NodeInterface[]
     */
    private $nodesByOccupiedDimensionSpacePoint;

    /**
     * @var array|DimensionSpacePointSet[]
     */
    private $coverageByOccupant;

    /**
     * @var array|NodeInterface[]
     */
    private $nodesByCoveredDimensionSpacePoint;

    /**
     * @var DimensionSpacePointSet
     */
    private $coveredDimensionSpacePoints;

    /**
     * This is not a dimension space point set since it is indexed by covered hash and not by member hash
     *
     * @var array|DimensionSpacePoint[]
     */
    private $occupationByCovered;

    /**
     * The dimension space point set this node aggregate disables.
     * This is *not* necessarily the set it is disabled in, since that is determined by its ancestors
     *
     * @var DimensionSpacePointSet
     */
    private $disabledDimensionSpacePoints;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeAggregateClassification $classification
     * @param NodeTypeName $nodeTypeName
     * @param NodeName|null $nodeName
     * @param OriginDimensionSpacePointSet $occupiedDimensionSpacePoints
     * @param array|NodeInterface[] $nodesByOccupiedDimensionSpacePoint
     * @param array|DimensionSpacePointSet[] $coverageByOccupant
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints
     * @param array|NodeInterface[] $nodesByCoveredDimensionSpacePoint
     * @param array|DimensionSpacePoint[] $occupationByCovered
     * @param DimensionSpacePointSet $disabledDimensionSpacePoints
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeAggregateClassification $classification,
        NodeTypeName $nodeTypeName,
        ?NodeName $nodeName,
        OriginDimensionSpacePointSet $occupiedDimensionSpacePoints,
        array $nodesByOccupiedDimensionSpacePoint,
        array $coverageByOccupant,
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
    public function getIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return NodeTypeName
     */
    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    /**
     * @return NodeName|null
     */
    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function getOccupiedDimensionSpacePoints(): OriginDimensionSpacePointSet
    {
        return $this->occupiedDimensionSpacePoints;
    }

    public function occupiesDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->occupiedDimensionSpacePoints->contains(OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint));
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

    public function getNodeByOccupiedDimensionSpacePoint(OriginDimensionSpacePoint $occupiedDimensionSpacePoint): NodeInterface
    {
        if (!$this->occupiedDimensionSpacePoints->contains($occupiedDimensionSpacePoint)) {
            throw new NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint('Node aggregate "' . $this->nodeAggregateIdentifier . '" does currently not occupy dimension space point ' . $occupiedDimensionSpacePoint, 1554902613);
        }

        return $this->nodesByOccupiedDimensionSpacePoint[$occupiedDimensionSpacePoint->getHash()];
    }

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->coveredDimensionSpacePoints;
    }

    public function coversDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->coveredDimensionSpacePoints->contains($dimensionSpacePoint);
    }

    public function getCoverageByOccupant(DimensionSpacePoint $occupiedDimensionSpacePoint): DimensionSpacePointSet
    {
        if (!isset($this->occupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->getHash()])) {
            throw new NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint('Node aggregate "' . $this->nodeAggregateIdentifier . '" does currently not occupy dimension space point ' . $occupiedDimensionSpacePoint, 1554902613);
        }

        return $this->coverageByOccupant[$occupiedDimensionSpacePoint->getHash()];
    }

    public function getNodesByCoveredDimensionSpacePoint(): array
    {
        return $this->nodesByCoveredDimensionSpacePoint;
    }

    public function getNodeByCoveredDimensionSpacePoint(DimensionSpacePoint $coveredDimensionSpacePoint): NodeInterface
    {
        if (!isset($this->coveredDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()])) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint('Node aggregate "' . $this->nodeAggregateIdentifier . '" does currently not cover dimension space point ' . $coveredDimensionSpacePoint, 1554902892);
        }

        return $this->coveredDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()];
    }

    public function getOccupationByCovered(DimensionSpacePoint $coveredDimensionSpacePoint): OriginDimensionSpacePoint
    {
        if (!isset($this->occupationByCovered[$coveredDimensionSpacePoint->getHash()])) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint('Node aggregate "' . $this->nodeAggregateIdentifier . '" does currently not cover dimension space point ' . $coveredDimensionSpacePoint, 1554902892);
        }

        return $this->occupationByCovered[$coveredDimensionSpacePoint->getHash()];
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
