<?php
namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 *
 */
class ContentGraphAdapter implements ContentGraphAdapterInterface
{
    public function __construct(
        private readonly Connection $dbalConnection,
        private readonly string $tableNamePrefix
    )
    {
    }

    public function rootNodeAggregateWithTypeExists(ContentStreamId $contentStreamId, NodeTypeName $nodeTypeName): bool
    {
        // TODO: Implement rootNodeAggregateWithTypeExists() method.
    }

    public function findParentNodeAggregates(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, NodeAggregateId $childNodeAggregateId): iterable
    {
        // TODO: Implement findParentNodeAggregates() method.
    }

    public function findNodeAggregateById(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId): ?NodeAggregate
    {
        // TODO: Implement findNodeAggregateById() method.
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, NodeAggregateId $childNodeAggregateId, OriginDimensionSpacePoint $childOriginDimensionSpacePoint): ?NodeAggregate
    {
        // TODO: Implement findParentNodeAggregateByChildOriginDimensionSpacePoint() method.
    }

    public function findChildNodeAggregates(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, NodeAggregateId $parentNodeAggregateId): iterable
    {
        // TODO: Implement findChildNodeAggregates() method.
    }

    public function findTetheredChildNodeAggregates(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, NodeAggregateId $parentNodeAggregateId): iterable
    {
        // TODO: Implement findTetheredChildNodeAggregates() method.
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(ContentStreamId $contentStreamId, NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        // TODO: Implement getDimensionSpacePointsOccupiedByChildNodeName() method.
    }

    public function findChildNodeAggregatesByName(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, NodeAggregateId $parentNodeAggregateId, NodeName $name): iterable
    {
        // TODO: Implement findChildNodeAggregatesByName() method.
    }

    public function subgraphContainsNodes(ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): bool
    {
        // TODO: Implement subgraphContainsNodes() method.
    }

    public function findNodeInSubgraph(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $nodeAggregateId): ?Node
    {
        // TODO: Implement findNodeInSubgraph() method.
    }

    public function findParentNodeInSubgraph(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $childNodeAggregateId): ?Node
    {
        // TODO: Implement findParentNodeInSubgraph() method.
    }

    public function findChildNodeByNameInSubgraph(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, NodeName $nodeNamex): ?Node
    {
        // TODO: Implement findChildNodeByNameInSubgraph() method.
    }

    public function findPreceedingSiblingNodesInSubgraph(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $startingSiblingNodeAggregateId): Nodes
    {
        // TODO: Implement findPreceedingSiblingNodesInSubgraph() method.
    }

    public function findSuceedingSiblingNodesInSubgraph(ContentStreamId $contentStreamId, WorkspaceName $workspaceName, DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $startingSiblingNodeAggregateId): Nodes
    {
        // TODO: Implement findSuceedingSiblingNodesInSubgraph() method.
    }

    public function hasContentStream(ContentStreamId $contentStreamId): bool
    {
        // TODO: Implement hasContentStream() method.
    }

    public function findStateForContentStream(ContentStreamId $contentStreamId): ?ContentStreamState
    {
        // TODO: Implement findStateForContentStream() method.
    }

    public function findVersionForContentStream(ContentStreamId $contentStreamId): MaybeVersion
    {
        // TODO: Implement findVersionForContentStream() method.
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        // TODO: Implement findWorkspaceByName() method.
    }

    public function findWorkspaceByCurrentContentStreamId(ContentStreamId $contentStreamId): ?Workspace
    {
        // TODO: Implement findWorkspaceByCurrentContentStreamId() method.
    }
}
