<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

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
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * The interface to implemented by all (readable) node aggregates that are to be used for hard or soft constraint checks.
 */
interface ReadableNodeAggregateInterface
{
    public function getIdentifier(): NodeAggregateIdentifier;

    public function getNodeTypeName(): NodeTypeName;

    public function getNodeName(): ?NodeName;

    public function getOccupiedDimensionSpacePoints(): DimensionSpacePointSet;

    /**
     * A node aggregate occupies a dimension space point if any node originates in it.
     *
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return bool
     */
    public function occupiesDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool;

    public function getOccupationByCovered(DimensionSpacePoint $coveredDimensionSpacePoint): DimensionSpacePoint;

    /**
     * @return array|NodeInterface[]
     */
    public function getNodesByOccupiedDimensionSpacePoint(): array;

    public function getNodeByOccupiedDimensionSpacePoint(DimensionSpacePoint $occupiedDimensionSpacePoint): NodeInterface;

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet;

    /**
     * A node aggregate covers a dimension space point if any node is visible in it
     * in that is has an incoming edge in it.
     *
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return bool
     */
    public function coversDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool;

    public function getCoverageByOccupant(DimensionSpacePoint $occupiedDimensionSpacePoint): DimensionSpacePointSet;

    /**
     * @return array|NodeInterface[]
     */
    public function getNodesByCoveredDimensionSpacePoint(): array;

    public function getNodeByCoveredDimensionSpacePoint(DimensionSpacePoint $coveredDimensionSpacePoint): NodeInterface;

    public function getClassification(): NodeAggregateClassification;

    public function isRoot(): bool;

    public function isTethered(): bool;
}
