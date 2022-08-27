<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\CoverageByOrigin;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginByCoverage;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;

/**
 * Node aggregate read model. Returned mainly from {@see ContentGraphInterface}.
 *
 * A *Node Aggregate* is the set of all nodes across different dimensions which belong to each other; i.e.
 * which represent the same "thing" (the same Page, the same Text node, the same Product).
 *
 * The system guarantees the following invariants:
 *
 * - Inside a NodeAggregate, each DimensionSpacePoint has at most one Node which covers it.
 *   To check this, the ReadableNodeAggregateInterface is used (mainly in constraint checks).
 * - The NodeType is always the same for all Nodes in a NodeAggregate
 * - all Nodes inside the NodeAggregate always have the same NodeName.
 * - all nodes inside a NodeAggregate are all of the same *classification*, which can be:
 *   - *root*: for root nodes
 *   - *tethered*: for nodes "attached" to the parent node (i.e. the old "AutoCreatedChildNodes")
 *   - *regular*: for all other nodes.
 *
 * This interface is called *Readable* because it exposes read operations on the set of nodes inside
 * a single NodeAggregate; often used for constraint checks (in command handlers).
 *
 * @api
 */
final class NodeAggregate
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeAggregateClassification $classification,
        public readonly NodeTypeName $nodeTypeName,
        public readonly ?NodeName $nodeName,
        public readonly OriginDimensionSpacePointSet $occupiedDimensionSpacePoints,
        /** @var array<string,Node> */
        private readonly array $nodesByOccupiedDimensionSpacePoint,
        private readonly CoverageByOrigin $coverageByOccupant,
        public readonly DimensionSpacePointSet $coveredDimensionSpacePoints,
        /** @var array<string,Node> */
        private readonly array $nodesByCoveredDimensionSpacePoint,
        private readonly OriginByCoverage $occupationByCovered,
        /**
         * The dimension space point set this node aggregate disables.
         * This is *not* necessarily the set it is disabled in, since that is determined by its ancestors
         */
        public readonly DimensionSpacePointSet $disabledDimensionSpacePoints
    ) {
    }

    public function occupiesDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): bool
    {
        return $this->occupiedDimensionSpacePoints->contains($originDimensionSpacePoint);
    }

    /**
     * Returns the nodes belonging to this aggregate, i.e. the "real materialized" node rows.
     *
     * @return iterable<int,Node>
     */
    public function getNodes(): iterable
    {
        return array_values($this->nodesByOccupiedDimensionSpacePoint);
    }

    public function getNodeByOccupiedDimensionSpacePoint(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): Node {
        if (!$this->occupiedDimensionSpacePoints->contains($occupiedDimensionSpacePoint)) {
            throw NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateIdentifier,
                $occupiedDimensionSpacePoint
            );
        }

        return $this->nodesByOccupiedDimensionSpacePoint[$occupiedDimensionSpacePoint->hash];
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

    public function getNodeByCoveredDimensionSpacePoint(DimensionSpacePoint $coveredDimensionSpacePoint): Node
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

    public function disablesDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->disabledDimensionSpacePoints->contains($dimensionSpacePoint);
    }
}
