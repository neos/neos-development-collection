<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphWithRuntimeCaches\ContentSubgraphWithRuntimeCaches;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @var array<string,ContentSubgraphWithRuntimeCaches>
     */
    private array $subgraphs = [];

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
    }

    final public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamId->value . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraphWithRuntimeCaches(
                new ContentSubgraph(
                    $this->contentRepositoryId,
                    $contentStreamId,
                    $dimensionSpacePoint,
                    $visibilityConstraints,
                    $this->client,
                    $this->nodeFactory,
                    $this->nodeTypeManager,
                    $this->tableNamePrefix
                )
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws RootNodeAggregateDoesNotExist
     */
    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            $contentStreamId,
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        $rootNodeAggregate = $rootNodeAggregates->first();

        if ($rootNodeAggregate === null) {
            throw RootNodeAggregateDoesNotExist::butWasExpectedTo($nodeTypeName);
        }

        return $rootNodeAggregate;
    }

    public function findRootNodeAggregates(
        ContentStreamId $contentStreamId,
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamid, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                    FROM ' . $this->tableNamePrefix . '_node n
                        JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                    WHERE h.contentstreamid = :contentStreamId
                        AND h.parentnodeanchor = :rootEdgeParentAnchorId ';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'rootEdgeParentAnchorId' => NodeRelationAnchorPoint::forRootEdge()->value,
        ];

        if ($filter->nodeTypeName !== null) {
            $query .= ' AND n.nodetypename = :nodeTypeName';
            $parameters['nodeTypeName'] = $filter->nodeTypeName->value;
        }


        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();


        /** @var \Traversable<NodeAggregate> $nodeAggregates The factory will return a NodeAggregate since the array is not empty */
        $nodeAggregates = $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );

        return NodeAggregates::fromArray(iterator_to_array($nodeAggregates));
    }

    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamid, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_node n
                    JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                WHERE h.contentstreamid = :contentStreamId
                    AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'nodeTypeName' => $nodeTypeName->value,
        ];

        $resultStatement = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $resultStatement,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamid, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = h.childnodeanchor
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = n.nodeaggregateid
                          AND r.contentstreamid = h.contentstreamid
                          AND r.affectednodeaggregateid = n.nodeaggregateid
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateid = :nodeAggregateId
                      AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'nodeAggregateId' => $nodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT p.*,
                      ph.name, ph.contentstreamid, ph.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node p
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                        ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                        ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_node c ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = p.nodeaggregateid
                          AND r.contentstreamid = ph.contentstreamid
                          AND r.affectednodeaggregateid = p.nodeaggregateid
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE c.nodeaggregateid = :nodeAggregateId
                      AND ph.contentstreamid = :contentStreamId
                      AND ch.contentstreamid = :contentStreamId';
        $parameters = [
            'nodeAggregateId' => $childNodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamid, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node n
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                          ON h.childnodeanchor = n.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = n.nodeaggregateid
                          AND r.contentstreamid = h.contentstreamid
                          AND r.affectednodeaggregateid = n.nodeaggregateid
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateid = (
                          SELECT p.nodeaggregateid FROM ' . $this->tableNamePrefix . '_node p
                          INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                              ON ch.parentnodeanchor = p.relationanchorpoint
                          INNER JOIN ' . $this->tableNamePrefix . '_node c
                              ON ch.childnodeanchor = c.relationanchorpoint
                          WHERE ch.contentstreamid = :contentStreamId
                          AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                          AND c.nodeaggregateid = :childNodeAggregateId
                          AND c.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                      )
                      AND h.contentstreamid = :contentStreamId';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'childNodeAggregateId' => $childNodeAggregateId->value,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|\Exception
     */
    public function findChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery();

        $parameters = [
            'parentNodeAggregateId' => $parentNodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|NodeTypeNotFoundException
     */
    public function findChildNodeAggregatesByName(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND ch.name = :relationName';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'parentNodeAggregateId' => $parentNodeAggregateId->value,
            'relationName' => $name->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|NodeTypeNotFoundException
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND c.classification = :tetheredClassification';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'parentNodeAggregateId' => $parentNodeAggregateId->value,
            'tetheredClassification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    private function createChildNodeAggregateQuery(): string
    {
        return 'SELECT c.*,
                      ch.name, ch.contentstreamid, ch.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node p
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                          ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                          ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_node c
                          ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = p.nodeaggregateid
                          AND r.contentstreamid = ph.contentstreamid
                          AND r.affectednodeaggregateid = p.nodeaggregateid
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE p.nodeaggregateid = :parentNodeAggregateId
                      AND ph.contentstreamid = :contentStreamId
                      AND ch.contentstreamid = :contentStreamId';
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeName $nodeName
     * @param NodeAggregateId $parentNodeAggregateId
     * @param OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     * @throws DBALException
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamId $contentStreamId,
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $connection = $this->client->getConnection();

        $query = 'SELECT h.dimensionspacepoint, h.dimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      INNER JOIN ' . $this->tableNamePrefix . '_node n
                          ON h.parentnodeanchor = n.relationanchorpoint
                      INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                          ON ph.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateid = :parentNodeAggregateId
                      AND n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash
                      AND ph.contentstreamid = :contentStreamId
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                      AND h.name = :nodeName';
        $parameters = [
            'parentNodeAggregateId' => $parentNodeAggregateId->value,
            'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
            'nodeName' => $nodeName->value
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];
        $dimensionSpacePoints = [];
        foreach (
            $connection->executeQuery($query, $parameters, $types)
                ->fetchAllAssociative() as $hierarchyRelationData
        ) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']]
                = DimensionSpacePoint::fromJsonString($hierarchyRelationData['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function countNodes(): int
    {
        $connection = $this->client->getConnection();
        $query = 'SELECT COUNT(*) FROM ' . $this->tableNamePrefix . '_node';

        $row = $connection->executeQuery($query)->fetchAssociative();

        return $row ? (int)$row['COUNT(*)'] : 0;
    }

    public function findUsedNodeTypeNames(): iterable
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery('SELECT DISTINCT nodetypename FROM ' . $this->tableNamePrefix . '_node')
            ->fetchAllAssociative();

        return array_map(function (array $row) {
            return NodeTypeName::fromString($row['nodetypename']);
        }, $rows);
    }

    /**
     * @return ContentSubgraphWithRuntimeCaches[]
     * @internal only used for {@see DoctrineDbalContentGraphProjection}
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }
}
