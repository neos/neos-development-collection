<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\DbalClient;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\Flow\Annotations as Flow;

/**
 * The PostgreSQL adapter content hypergraph
 *
 * To be used as a read-only source of subhypergraphs, node aggregates and nodes
 *
 * @Flow\Proxy(false)
 * @api
 */
final class ContentHypergraph implements ContentGraphInterface
{
    private DbalClient $databaseClient;

    private NodeFactory $nodeFactory;

    /**
     * @var array|ContentSubhypergraph[]
     */
    private array $subhypergraphs;

    public function __construct(DbalClient $databaseClient, NodeFactory $nodeFactory)
    {
        $this->databaseClient = $databaseClient;
        $this->nodeFactory = $nodeFactory;
    }

    public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ?ContentSubgraphInterface {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash() . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subhypergraphs[$index])) {
            $this->subhypergraphs[$index] = new ContentSubhypergraph(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->databaseClient,
                $this->nodeFactory
            );
        }

        return $this->subhypergraphs[$index];
    }

    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface {
        $query = /** @lang PostgreSQL */
            'SELECT n.origindimensionspacepoint, n.nodeaggregateidentifier, n.nodetypename, n.classification, n.properties, n.nodename,
                h.contentstreamidentifier
            FROM neos_contentgraph_hierarchyhyperrelation h,
            neos_contentgraph_node n
                JOIN (
                    SELECT jsonb_array_elements_text(childnodeanchors)::varchar relationanchorpoint
                    FROM neos_contentgraph_hierarchyhyperrelation
                ) nodes_with_hierarchyhyperrelations
                USING (relationanchorpoint)
                WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
                AND h.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->getHash()
        ];

        $nodeRow = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $this->nodeFactory->mapNodeRowToNode($nodeRow);
    }

    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate {
        // TODO: Implement findRootNodeAggregateByType() method.
    }

    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): \Iterator {
        // TODO: Implement findNodeAggregatesByType() method.
    }

    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */
            'SELECT n.origindimensionspacepoint, n.nodeaggregateidentifier, n.nodetypename, n.classification, n.properties, n.nodename,
                h.contentstreamidentifier, h.dimensionspacepoints
            FROM neos_contentgraph_hierarchyhyperrelation h,
            neos_contentgraph_node n
                JOIN (
                    SELECT jsonb_array_elements_text(childnodeanchors)::varchar relationanchorpoint
                    FROM neos_contentgraph_hierarchyhyperrelation
                ) nodes_with_hierarchyhyperrelations
                USING (relationanchorpoint)
                WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                AND h.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        $nodeRows = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        // TODO: Implement findParentNodeAggregateByChildOriginDimensionSpacePoint() method.
    }

    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): array {
        // TODO: Implement findParentNodeAggregates() method.
    }

    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array {
        // TODO: Implement findChildNodeAggregates() method.
    }

    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): array {
        // TODO: Implement findChildNodeAggregatesByName() method.
    }

    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array {
        // TODO: Implement findTetheredChildNodeAggregates() method.
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        // TODO: Implement getDimensionSpacePointsOccupiedByChildNodeName() method.
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = 'SELECT COUNT(*) FROM ' . NodeRecord::TABLE_NAME;

        return $this->getDatabaseConnection()->executeQuery($query)->fetchOne();
    }

    public function findProjectedContentStreamIdentifiers(): array
    {
        // TODO: Implement findProjectedContentStreamIdentifiers() method.
    }

    public function findProjectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        // TODO: Implement findProjectedDimensionSpacePoints() method.
    }

    public function findProjectedNodeAggregateIdentifiersInContentStream(
        ContentStreamIdentifier $contentStreamIdentifier
    ): array {
        // TODO: Implement findProjectedNodeAggregateIdentifiersInContentStream() method.
    }

    public function findProjectedNodeTypes(): iterable
    {
        // TODO: Implement findProjectedNodeTypes() method.
    }

    public function enableCache(): void
    {
        // TODO: Implement enableCache() method.
    }

    public function disableCache(): void
    {
        // TODO: Implement disableCache() method.
    }

    private function getDatabaseConnection(): DatabaseConnection
    {
        return $this->databaseClient->getConnection();
    }
}
