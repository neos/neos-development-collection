<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\NodeType\NodeTypeName;
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
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeTypeName $nodeTypeName,

    ) {}

    public function addVariant(OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateIdentifier $parentNodeAggregateIdentifier): void
    {
        if (isset($this->variants[$originDimensionSpacePoint->hash])) {
            throw new MigrationException(sprintf('Node "%s" with dimension space point "%s" was already visited before', $this->nodeAggregateIdentifier, $originDimensionSpacePoint), 1653050442);
        }
        $this->variants[$originDimensionSpacePoint->hash] = new VisitedNodeVariant($originDimensionSpacePoint, $parentNodeAggregateIdentifier);
    }

    public function getOriginDimensionSpacePoints(): OriginDimensionSpacePointSet
    {
        return new OriginDimensionSpacePointSet(array_map(static fn (VisitedNodeVariant $nodeVariant) => $nodeVariant->originDimensionSpacePoint, $this->variants));
    }

    public function getVariant(OriginDimensionSpacePoint $originDimensionSpacePoint): VisitedNodeVariant
    {
        if (!isset($this->variants[$originDimensionSpacePoint->hash])) {
            throw new \InvalidArgumentException(sprintf('Variant %s of node "%s" has not been visited before', $originDimensionSpacePoint, $this->nodeAggregateIdentifier), 1656058159);
        }
        return $this->variants[$originDimensionSpacePoint->hash];
    }
}
