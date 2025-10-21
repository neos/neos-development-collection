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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Node aggregate read model. Returned mainly from {@see ContentGraphInterface}.
 *
 * A *Node Aggregate* is the set of all nodes across different dimensions which belong to each other; i.e.
 * which represent the same "thing" (the same Page, the same Text node, the same Product).
 *
 * The system guarantees the following invariants:
 *
 * - Inside a NodeAggregate, each DimensionSpacePoint has at most one Node which covers it.
 *   To check this, this class is used (mainly in constraint checks).
 * - The NodeType is always the same for all Nodes in a NodeAggregate
 * - all Nodes inside the NodeAggregate always have the same NodeName.
 * - all nodes inside a NodeAggregate are all of the same *classification*, which can be:
 *   - *root*: for root nodes
 *   - *tethered*: for nodes "attached" to the parent node (i.e. the old "AutoCreatedChildNodes")
 *   - *regular*: for all other nodes.
 *
 * @api Note: The constructor is not part of the public API
 */
final readonly class NodeAggregate
{
    /**
     * @param ContentRepositoryId $contentRepositoryId The content-repository this node aggregate belongs to
     * @param WorkspaceName $workspaceName The workspace of this node aggregate
     * @param NodeAggregateId $nodeAggregateId ID of this node aggregate
     * @param NodeAggregateClassification $classification whether this node aggregate represents a root, regular or tethered node
     * @param NodeTypeName $nodeTypeName name of the node type of this node aggregate
     * @param NodeName|null $nodeName optional name of this node aggregate
     * @param OriginDimensionSpacePointSet $occupiedDimensionSpacePoints dimension space points this node aggregate occupies
     * @param non-empty-array<string,Node> $nodesByOccupiedDimensionSpacePoint At least one node will be occupied.
     * @param CoverageByOrigin $coverageByOccupant
     * @param DimensionSpacePointSet $coveredDimensionSpacePoints This node aggregate will cover at least one dimension space.
     * @param OriginByCoverage $occupationByCovered
     * @param non-empty-array<string,NodeTags> $nodeTagsByCoveredDimensionSpacePoint node tags by every covered dimension space point
     */
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public NodeAggregateClassification $classification,
        public NodeTypeName $nodeTypeName,
        public ?NodeName $nodeName,
        public OriginDimensionSpacePointSet $occupiedDimensionSpacePoints,
        private array $nodesByOccupiedDimensionSpacePoint,
        public CoverageByOrigin $coverageByOccupant,
        public DimensionSpacePointSet $coveredDimensionSpacePoints,
        private OriginByCoverage $occupationByCovered,
        private array $nodeTagsByCoveredDimensionSpacePoint,
    ) {
    }

    /**
     * @param non-empty-array<string,Node> $nodesByOccupiedDimensionSpacePoint
     * @param non-empty-array<string,NodeTags> $nodeTagsByCoveredDimensionSpacePoint
     * @internal The signature of this method can change in the future!
     */
    public static function create(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        NodeAggregateClassification $classification,
        NodeTypeName $nodeTypeName,
        ?NodeName $nodeName,
        OriginDimensionSpacePointSet $occupiedDimensionSpacePoints,
        array $nodesByOccupiedDimensionSpacePoint,
        CoverageByOrigin $coverageByOccupant,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        OriginByCoverage $occupationByCovered,
        array $nodeTagsByCoveredDimensionSpacePoint,
    ): self {
        return new self(
            $contentRepositoryId,
            $workspaceName,
            $nodeAggregateId,
            $classification,
            $nodeTypeName,
            $nodeName,
            $occupiedDimensionSpacePoints,
            $nodesByOccupiedDimensionSpacePoint,
            $coverageByOccupant,
            $coveredDimensionSpacePoints,
            $occupationByCovered,
            $nodeTagsByCoveredDimensionSpacePoint,
        );
    }

    public function occupiesDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): bool
    {
        return $this->occupiedDimensionSpacePoints->contains($originDimensionSpacePoint);
    }

    /**
     * Returns the node for the occupied dimension space point.
     *
     * The node aggregate does only know about nodes from dimensions where they originate in.
     * Fallback nodes are not part of the node aggregate as there is currently no use-case.
     *
     * To fetch the occupying node by covered dimension space point use {@see self::getOccupationByCovered}:
     *
     *     $node = $this->getNodeByOccupiedDimensionSpacePoint(
     *         $this->getOccupationByCovered($coveredDimensionSpacePoint)
     *     );
     */
    public function getNodeByOccupiedDimensionSpacePoint(
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): Node {
        if (!$this->occupiedDimensionSpacePoints->contains($occupiedDimensionSpacePoint)) {
            throw NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateId,
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
                $this->nodeAggregateId,
                $occupiedDimensionSpacePoint
            );
        }

        return $coverage;
    }

    public function getOccupationByCovered(DimensionSpacePoint $coveredDimensionSpacePoint): OriginDimensionSpacePoint
    {
        $occupation = $this->occupationByCovered->getOrigin($coveredDimensionSpacePoint);
        if (is_null($occupation)) {
            throw NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint::butWasSupposedTo(
                $this->nodeAggregateId,
                $coveredDimensionSpacePoint
            );
        }

        return $occupation;
    }

    /**
     * Get the dimension space points this node aggregate is tagged according to the provided tag
     *
     * Implementation note:
     *
     * We need to pass ${@see $nodeTagsByCoveredDimensionSpacePoint} additionally to the NodeAggregate as this information doesn't exist otherwise.
     * The node aggregate only knows about its occupying nodes {@see $nodesByOccupiedDimensionSpacePoint} - so no fallbacks.
     * This means we CANNOT substitute this implementation by iterating over the occupied nodes as explicitly tagged specialisations will not show up as tagged.
     *
     * We could simplify this logic if we also add these specialisation node rows explicitly to the NodeAggregate, but currently there is no use for that.
     *
     * @param bool $withoutInherited only dimensions where the subtree tag was set explicitly will be returned, taking inheritance out of account {@see NodeTags::withoutInherited()}
     * @internal Experimental api, this is a low level concept that is mostly not meant to be used outside the core or tests
     */
    public function getCoveredDimensionsTaggedBy(SubtreeTag $subtreeTag, bool $withoutInherited): DimensionSpacePointSet
    {
        $explicitlyTagged = [];
        foreach ($this->coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $nodeTags = $this->nodeTagsByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash];
            $tagExistsInDimension = match ($withoutInherited) {
                true => $nodeTags->withoutInherited()->contain($subtreeTag),
                false => $nodeTags->contain($subtreeTag)
            };
            if ($tagExistsInDimension) {
                $explicitlyTagged[] = $coveredDimensionSpacePoint;
            }
        }
        return DimensionSpacePointSet::fromArray($explicitlyTagged);
    }

    /**
     * Returns the nodes belonging to this aggregate, i.e. the "real materialized" node rows.
     *
     * @internal Using this method to access all occupied nodes or possibly extract a single arbitrary node is not intended for use outside the core.
     * @return iterable<int,Node>
     */
    public function getNodes(): iterable
    {
        return array_values($this->nodesByOccupiedDimensionSpacePoint);
    }
}
