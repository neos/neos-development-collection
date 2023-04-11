<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class VisitedNodeAggregates
{

    /**
     * @var array<string, VisitedNodeAggregate>
     */
    private array $byPathAndDimensionSpacePoint = [];

    /**
     * @var array<string, VisitedNodeAggregate>
     */
    private array $byNodeAggregateId = [];

    public function addRootNode(NodeAggregateId $nodeAggregateId, NodeTypeName $nodeTypeName, NodePath $nodePath, DimensionSpacePointSet $allowedDimensionSubspace): void
    {
        $this->add($nodeAggregateId, $allowedDimensionSubspace, $nodeTypeName, $nodePath, NodeAggregateId::fromString('00000000-0000-0000-0000-000000000000'));
    }

    public function add(NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $coveredDimensionSpacePoints, NodeTypeName $nodeTypeName, NodePath $nodePath, NodeAggregateId $parentNodeAggregateId): void
    {
        $visitedNodeAggregate = $this->byNodeAggregateId[$nodeAggregateId->value] ?? new VisitedNodeAggregate($nodeAggregateId, $nodeTypeName);
        if (!$nodeTypeName->equals($visitedNodeAggregate->nodeTypeName)) {
            throw new MigrationException(sprintf('Node aggregate with id "%s" has a type of "%s" in content dimension %s. I was visited previously for content dimension %s with the type "%s". Node variants must not have different types', $nodeAggregateId, $nodeTypeName, $coveredDimensionSpacePoints, $visitedNodeAggregate->getOriginDimensionSpacePoints(), $visitedNodeAggregate->nodeTypeName), 1655913685);
        }
        foreach ($coveredDimensionSpacePoints as $dimensionSpacePoint) {
            $visitedNodeAggregate->addVariant(OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint), $parentNodeAggregateId);
            $pathAndDimensionSpacePointHash = $nodePath->jsonSerialize() . '__' . $dimensionSpacePoint->hash;
            if (isset($this->byPathAndDimensionSpacePoint[$pathAndDimensionSpacePointHash])) {
                throw new MigrationException(sprintf('Node "%s" with path "%s" and dimension space point "%s" was already visited before', $nodeAggregateId, $nodePath, $dimensionSpacePoint), 1655900356);
            }
            $this->byPathAndDimensionSpacePoint[$pathAndDimensionSpacePointHash] = $visitedNodeAggregate;
        }
        $this->byNodeAggregateId[$nodeAggregateId->value] = $visitedNodeAggregate;
    }

    public function containsNodeAggregate(NodeAggregateId $nodeAggregateId): bool
    {
        return isset($this->byNodeAggregateId[$nodeAggregateId->value]);
    }

    public function getByNodeAggregateId(NodeAggregateId $nodeAggregateId): VisitedNodeAggregate
    {
        if (!isset($this->byNodeAggregateId[$nodeAggregateId->value])) {
            throw new \InvalidArgumentException(sprintf('Node aggregate with id "%s" has not been visited before', $nodeAggregateId), 1655912733);
        }
        return $this->byNodeAggregateId[$nodeAggregateId->value];
    }

    public function alreadyVisitedOriginDimensionSpacePoints(NodeAggregateId $nodeAggregateId): OriginDimensionSpacePointSet
    {
        return isset($this->byNodeAggregateId[$nodeAggregateId->value]) ? $this->byNodeAggregateId[$nodeAggregateId->value]->getOriginDimensionSpacePoints() : OriginDimensionSpacePointSet::fromArray([]);
    }

    public function findMostSpecificParentNodeInDimensionGraph(NodePath $nodePath, OriginDimensionSpacePoint $originDimensionSpacePoint, InterDimensionalVariationGraph $interDimensionalVariationGraph): ?VisitedNodeAggregate
    {
        $dimensionSpacePoint = $originDimensionSpacePoint->toDimensionSpacePoint();
        $nodePathParts = $nodePath->getParts();
        array_pop($nodePathParts);
        $parentPath = NodePath::fromPathSegments($nodePathParts);
        while ($dimensionSpacePoint !== null) {
            $parentPathAndDimensionSpacePointHash = strtolower($parentPath->jsonSerialize()) . '__' . $dimensionSpacePoint->hash;
            if (isset($this->byPathAndDimensionSpacePoint[$parentPathAndDimensionSpacePointHash])) {
                return $this->byPathAndDimensionSpacePoint[$parentPathAndDimensionSpacePointHash];
            }
            $dimensionSpacePoint = $interDimensionalVariationGraph->getPrimaryGeneralization($dimensionSpacePoint);
        }
        return null;
    }


}
