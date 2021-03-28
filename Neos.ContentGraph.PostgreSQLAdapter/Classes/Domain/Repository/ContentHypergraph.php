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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
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
 * @Flow\Scope("singleton")
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
        $query = HypergraphQuery::create($contentStreamIdentifier);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateIdentifier);

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

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
        $query = HypergraphQuery::create($contentStreamIdentifier, true);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateIdentifier);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = HypergraphParentQuery::create($contentStreamIdentifier);
        $query = $query->withChildNodeAggregateIdentifier($childNodeAggregateIdentifier)
            ->withChildOriginDimensionSpacePoint($childOriginDimensionSpacePoint);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): array {
        $query = HypergraphParentQuery::create($contentStreamIdentifier);
        $query = $query->withChildNodeAggregateIdentifier($nodeAggregateIdentifier);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
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
        $query = HypergraphChildQuery::create($contentStreamIdentifier);
        $query = $query->withParentNodeAggregateIdentifier($parentNodeAggregateIdentifier)
            ->withChildNodeName($name);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array {
        $query = HypergraphChildQuery::create($contentStreamIdentifier);
        $query = $query->withParentNodeAggregateIdentifier($parentNodeAggregateIdentifier)
            ->withOnlyTethered();

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $query = HypergraphChildQuery::create($contentStreamIdentifier, ['ch.dimensionspacepoint, ch.dimensionspacepointhash']);
        $query = $query->withChildNodeName($nodeName)
            ->withParentNodeAggregateIdentifier($parentNodeAggregateIdentifier)
            ->withOriginDimensionSpacePoint($parentNodeOriginOriginDimensionSpacePoint)
            ->withDimensionSpacePoints($dimensionSpacePointsToCheck);

        $occupiedDimensionSpacePoints = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $row) {
            $occupiedDimensionSpacePoints[$row['ch.dimensionspacepointhash']] = DimensionSpacePoint::fromJsonString($row['ch.dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($occupiedDimensionSpacePoints);
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
