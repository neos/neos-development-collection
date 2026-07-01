<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

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
     * Resolves the dimension space points the given occupant still covers after all variants of its node aggregate
     * have been processed.
     *
     * Each creation/variation event claims coverage of the specialization set of its origin, excluding the origins
     * visited before it, and later events take over the dimension space points they claim. The final coverage of the
     * given occupant is therefore its own claim minus everything that variants visited after it claimed for themselves.
     */
    public function getCoverageByOccupant(OriginDimensionSpacePoint $occupant, InterDimensionalVariationGraph $interDimensionalVariationGraph): DimensionSpacePointSet
    {
        $coverage = new DimensionSpacePointSet([]);
        $previouslyVisitedDimensionSpacePoints = new DimensionSpacePointSet([]);
        foreach ($this->variants as $variant) {
            $claimedDimensionSpacePoints = $interDimensionalVariationGraph->getSpecializationSet($variant->originDimensionSpacePoint->toDimensionSpacePoint(), true, $previouslyVisitedDimensionSpacePoints);
            if ($variant->originDimensionSpacePoint->equals($occupant)) {
                $coverage = $claimedDimensionSpacePoints;
            } else {
                $coverage = $coverage->getDifference($claimedDimensionSpacePoints);
            }
            $previouslyVisitedDimensionSpacePoints = $previouslyVisitedDimensionSpacePoints->getUnion(new DimensionSpacePointSet([$variant->originDimensionSpacePoint->toDimensionSpacePoint()]));
        }
        return $coverage;
    }
}
