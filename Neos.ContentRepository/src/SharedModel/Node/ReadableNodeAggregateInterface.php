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

namespace Neos\ContentRepository\SharedModel\Node;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;

/**
 * Implemented by all (readable) node aggregates that are to be used for hard or soft constraint checks.
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
 */
interface ReadableNodeAggregateInterface
{
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    public function getIdentifier(): NodeAggregateIdentifier;

    public function getNodeTypeName(): NodeTypeName;

    public function getNodeName(): ?NodeName;

    public function getOccupiedDimensionSpacePoints(): OriginDimensionSpacePointSet;

    /**
     * A node aggregate occupies a dimension space point if any node originates in it.
     */
    public function occupiesDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): bool;

    public function getOccupationByCovered(DimensionSpacePoint $coveredDimensionSpacePoint): OriginDimensionSpacePoint;

    /**
     * @return iterable<int,NodeInterface>
     */
    public function getNodes(): iterable;

    public function getNodeByOccupiedDimensionSpacePoint(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): NodeInterface;

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet;

    /**
     * A node aggregate covers a dimension space point if any node is covers it
     * in that is has an incoming edge in it.
     *
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return bool
     */
    public function coversDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool;

    public function getCoverageByOccupant(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): DimensionSpacePointSet;

    /**
     * @return array|NodeInterface[]
     */
    public function getNodesByCoveredDimensionSpacePoint(): array;

    public function getNodeByCoveredDimensionSpacePoint(DimensionSpacePoint $coveredDimensionSpacePoint): NodeInterface;

    public function getDisabledDimensionSpacePoints(): DimensionSpacePointSet;

    public function disablesDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool;

    public function getClassification(): NodeAggregateClassification;

    public function isRoot(): bool;

    public function isTethered(): bool;
}
