<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
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

    public function addVariant(OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId): void
    {
        if (isset($this->variants[$originDimensionSpacePoint->hash])) {
            throw new MigrationException(sprintf('Node "%s" with dimension space point "%s" was already visited before', $this->nodeAggregateId, $originDimensionSpacePoint), 1653050442);
        }
        $this->variants[$originDimensionSpacePoint->hash] = new VisitedNodeVariant($originDimensionSpacePoint, $parentNodeAggregateId);
    }

    public function getOriginDimensionSpacePoints(): OriginDimensionSpacePointSet
    {
        return new OriginDimensionSpacePointSet(array_map(static fn (VisitedNodeVariant $nodeVariant) => $nodeVariant->originDimensionSpacePoint, $this->variants));
    }

    public function getVariant(OriginDimensionSpacePoint $originDimensionSpacePoint): VisitedNodeVariant
    {
        if (!isset($this->variants[$originDimensionSpacePoint->hash])) {
            throw new \InvalidArgumentException(sprintf('Variant %s of node "%s" has not been visited before', $originDimensionSpacePoint, $this->nodeAggregateId), 1656058159);
        }
        return $this->variants[$originDimensionSpacePoint->hash];
    }
}
