<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
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
    private array $byNodeAggregateIdentifier = [];

    public function addRootNode(NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeName $nodeTypeName, NodePath $nodePath, DimensionSpacePointSet $allowedDimensionSubspace): void
    {
        $this->add($nodeAggregateIdentifier, $allowedDimensionSubspace, $nodeTypeName, $nodePath, NodeAggregateIdentifier::fromString('00000000-0000-0000-0000-000000000000'));
    }

    public function add(NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePointSet $coveredDimensionSpacePoints, NodeTypeName $nodeTypeName, NodePath $nodePath, NodeAggregateIdentifier $parentNodeAggregateIdentifier): void
    {
        $visitedNodeAggregate = $this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()] ?? new VisitedNodeAggregate($nodeAggregateIdentifier, $nodeTypeName);
        if (!$nodeTypeName->equals($visitedNodeAggregate->nodeTypeName)) {
            throw new MigrationException(sprintf('Node aggregate with id "%s" has a type of "%s" in content dimension %s. I was visited previously for content dimension %s with the type "%s". Node variants must not have different types', $nodeAggregateIdentifier, $nodeTypeName, $coveredDimensionSpacePoints, $visitedNodeAggregate->getOriginDimensionSpacePoints(), $visitedNodeAggregate->nodeTypeName), 1655913685);
        }
        foreach ($coveredDimensionSpacePoints as $dimensionSpacePoint) {
            $visitedNodeAggregate->addVariant(OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint), $parentNodeAggregateIdentifier);
            $pathAndDimensionSpacePointHash = $nodePath->jsonSerialize() . '__' . $dimensionSpacePoint->hash;
            if (isset($this->byPathAndDimensionSpacePoint[$pathAndDimensionSpacePointHash])) {
                throw new MigrationException(sprintf('Node "%s" with path "%s" and dimension space point "%s" was already visited before', $nodeAggregateIdentifier, $nodePath, $dimensionSpacePoint), 1655900356);
            }
            $this->byPathAndDimensionSpacePoint[$pathAndDimensionSpacePointHash] = $visitedNodeAggregate;
        }
        $this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()] = $visitedNodeAggregate;
    }

    public function containsNodeAggregate(NodeAggregateIdentifier $nodeAggregateIdentifier): bool
    {
        return isset($this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()]);
    }

    public function getByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): VisitedNodeAggregate
    {
        if (!isset($this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()])) {
            throw new \InvalidArgumentException(sprintf('Node aggregate with id "%s" has not been visited before', $nodeAggregateIdentifier), 1655912733);
        }
        return $this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()];
    }

    public function alreadyVisitedOriginDimensionSpacePoints(NodeAggregateIdentifier $nodeAggregateIdentifier): OriginDimensionSpacePointSet
    {
        return isset($this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()]) ? $this->byNodeAggregateIdentifier[$nodeAggregateIdentifier->getValue()]->getOriginDimensionSpacePoints() : OriginDimensionSpacePointSet::fromArray([]);
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
