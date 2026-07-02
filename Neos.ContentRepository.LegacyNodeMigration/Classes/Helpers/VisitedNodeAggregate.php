<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class VisitedNodeAggregate
{

    /**
     * @var array<VisitedNodeVariant>
     */
    private array $variants = [];

    public function __construct(
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $nodeTypeName,

    ) {}

    public function addVariant(OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, PropertyNames $propertyNames): void
    {
        if (isset($this->variants[$originDimensionSpacePoint->hash])) {
            throw new MigrationException(sprintf('Node "%s" with dimension space point "%s" was already visited before', $this->nodeAggregateId->value, $originDimensionSpacePoint->toJson()), 1653050442);
        }
        $this->variants[$originDimensionSpacePoint->hash] = new VisitedNodeVariant($originDimensionSpacePoint, $parentNodeAggregateId, $propertyNames);
    }

    public function getOriginDimensionSpacePoints(): OriginDimensionSpacePointSet
    {
        return new OriginDimensionSpacePointSet(array_map(static fn (VisitedNodeVariant $nodeVariant) => $nodeVariant->originDimensionSpacePoint, $this->variants));
    }

    public function getVariant(OriginDimensionSpacePoint $originDimensionSpacePoint): VisitedNodeVariant
    {
        if (!isset($this->variants[$originDimensionSpacePoint->hash])) {
            throw new \InvalidArgumentException(sprintf('Variant %s of node "%s" has not been visited before', $originDimensionSpacePoint->toJson(), $this->nodeAggregateId->value), 1656058159);
        }
        return $this->variants[$originDimensionSpacePoint->hash];
    }

    /**
     * Resolves the dimension space points whose visibility follows the given (hidden) occupant, i.e. the points a
     * disable tag for the occupant must affect after all variants of its node aggregate have been processed.
     *
     * These are, on the one hand, the dimension space points the occupant still covers in the exported structure:
     * each creation/variation event claims coverage of the specialization set of its origin, excluding the origins
     * visited before it, and later events take over the dimension space points they claim. The occupant keeps its
     * own claim minus everything that variants visited after it claimed for themselves.
     *
     * On the other hand, these are the dimension space points that resolved to the occupant in the legacy content
     * repository: a dimension without an own node data row inherited the nearest variant in its fallback chain,
     * including its hidden state, no matter in which order the rows were processed. Such points must stay disabled
     * even if a variant visited after the occupant claimed them for the exported structure.
     */
    public function resolveAffectedDimensionSpacePoints(OriginDimensionSpacePoint $occupant, InterDimensionalVariationGraph $interDimensionalVariationGraph): DimensionSpacePointSet
    {
        $affectedDimensionSpacePoints = new DimensionSpacePointSet([]);
        $previouslyVisitedDimensionSpacePoints = new DimensionSpacePointSet([]);
        foreach ($this->variants as $variant) {
            $claimedDimensionSpacePoints = $interDimensionalVariationGraph->getSpecializationSet($variant->originDimensionSpacePoint->toDimensionSpacePoint(), true, $previouslyVisitedDimensionSpacePoints);
            if ($variant->originDimensionSpacePoint->equals($occupant)) {
                $affectedDimensionSpacePoints = $claimedDimensionSpacePoints;
            } else {
                $affectedDimensionSpacePoints = $affectedDimensionSpacePoints->getDifference($claimedDimensionSpacePoints);
            }
            $previouslyVisitedDimensionSpacePoints = $previouslyVisitedDimensionSpacePoints->getUnion(new DimensionSpacePointSet([$variant->originDimensionSpacePoint->toDimensionSpacePoint()]));
        }
        foreach ($interDimensionalVariationGraph->getSpecializationSet($occupant->toDimensionSpacePoint(), true) as $specialization) {
            if ($this->fallbackResolvesToOccupant($specialization, $occupant, $interDimensionalVariationGraph)) {
                $affectedDimensionSpacePoints = $affectedDimensionSpacePoints->getUnion(new DimensionSpacePointSet([$specialization]));
            }
        }
        return $affectedDimensionSpacePoints;
    }

    /**
     * Whether the given dimension space point resolves to the occupant via the fallback mechanism, i.e. the occupant
     * is the nearest visited origin in its fallback chain, so the legacy content repository presented the occupant's
     * variant (and visibility) in this dimension space point.
     */
    private function fallbackResolvesToOccupant(DimensionSpacePoint $dimensionSpacePoint, OriginDimensionSpacePoint $occupant, InterDimensionalVariationGraph $interDimensionalVariationGraph): bool
    {
        $visitedOrigins = $this->getOriginDimensionSpacePoints()->toDimensionSpacePointSet();
        foreach ([$dimensionSpacePoint, ...$interDimensionalVariationGraph->getWeightedGeneralizations($dimensionSpacePoint)] as $fallback) {
            if ($visitedOrigins->contains($fallback)) {
                return $fallback->equals($occupant->toDimensionSpacePoint());
            }
        }
        return false;
    }
}
