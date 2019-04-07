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
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;

/**
 * Node aggregate read model
 */
final class NodeAggregate implements ReadableNodeAggregateInterface
{
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
     * @var array|NodeInterface[]
     */
    private $nodes;

    /**
     * @var DimensionSpacePointSet
     */
    private $occupiedDimensionSpacePoints;

    /**
     * @var DimensionSpacePointSet
     */
    private $coveredDimensionSpacePoints;

    public function __construct(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeAggregateClassification $classification,
        NodeTypeName $nodeTypeName,
        ?NodeName $nodeName,
        array $nodes,
        DimensionSpacePointSet $occupiedDimensionSpacePoints,
        DimensionSpacePointSet $coveredDimensionSpacePoints
    ) {
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->classification = $classification;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeName = $nodeName;
        $this->nodes = $nodes;
        $this->occupiedDimensionSpacePoints = $occupiedDimensionSpacePoints;
        $this->coveredDimensionSpacePoints = $coveredDimensionSpacePoints;
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

    /**
     * @return array|NodeInterface[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getOccupiedDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->occupiedDimensionSpacePoints;
    }

    public function occupiesDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->occupiedDimensionSpacePoints->contains($dimensionSpacePoint);
    }

    public function getCoveredDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->coveredDimensionSpacePoints;
    }

    public function coversDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        return $this->coveredDimensionSpacePoints->contains($dimensionSpacePoint);
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
