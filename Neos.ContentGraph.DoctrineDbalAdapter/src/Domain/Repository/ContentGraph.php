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
use Neos\ContentGraph\DoctrineDbalAdapter\Service\InMemoryCacheAccessor;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @api
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @var array<string,ContentSubgraph>
     */
    private array $subgraphs = [];

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly string $tableNamePrefix
    ) {
    }

    final public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamIdentifier . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraph(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->client,
                $this->nodeFactory,
                $this->tableNamePrefix
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws DBALException
     * @throws NodeTypeNotFoundException
     */
    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface {
        $connection = $this->client->getConnection();

        // HINT: we check the ContentStreamIdentifier on the EDGE;
        // as this is where we actually find out whether the node exists in the content stream
        $nodeRow = $connection->executeQuery(
            'SELECT n.*, h.contentstreamidentifier, h.name FROM ' . $this->tableNamePrefix . '_node n
                  INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                      ON h.childnodeanchor = n.relationanchorpoint
                  WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                  AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
                  AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        ) : null;
    }

    /**
     * @throws \Exception
     */
    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                    FROM ' . $this->tableNamePrefix . '_node n
                        JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.parentnodeanchor = :rootEdgeParentAnchorIdentifier
                        AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'rootEdgeParentAnchorIdentifier' => (string)NodeRelationAnchorPoint::forRootEdge(),
            'nodeTypeName' => (string)$nodeTypeName,
        ];

        $nodeRow = $connection->executeQuery($query, $parameters)->fetchAssociative();

        if (!is_array($nodeRow)) {
            throw new \RuntimeException('Root Node Aggregate not found');
        }

        /** @var NodeAggregate $nodeAggregate The factory will return a NodeAggregate since the array is not empty */
        $nodeAggregate = $this->nodeFactory->mapNodeRowsToNodeAggregate(
            [$nodeRow],
            VisibilityConstraints::withoutRestrictions()
        );

        return $nodeAggregate;
    }

    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_node n
                    JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'nodeTypeName' => (string)$nodeTypeName,
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
    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamidentifier, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = h.childnodeanchor
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.contentstreamidentifier = h.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT p.*,
                      ph.name, ph.contentstreamidentifier, ph.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node p
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                        ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                        ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_node c ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.contentstreamidentifier = ph.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE c.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamidentifier, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node n
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                          ON h.childnodeanchor = n.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.contentstreamidentifier = h.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateidentifier = (
                          SELECT p.nodeaggregateidentifier FROM ' . $this->tableNamePrefix . '_node p
                          INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                              ON ch.parentnodeanchor = p.relationanchorpoint
                          INNER JOIN ' . $this->tableNamePrefix . '_node c
                              ON ch.childnodeanchor = c.relationanchorpoint
                          WHERE ch.contentstreamidentifier = :contentStreamIdentifier
                          AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                          AND c.nodeaggregateidentifier = :childNodeAggregateIdentifier
                          AND c.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                      )
                      AND h.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery();

        $parameters = [
            'parentNodeAggregateIdentifier' => (string) $parentNodeAggregateIdentifier,
            'contentStreamIdentifier' => (string) $contentStreamIdentifier
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND ch.name = :relationName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'relationName' => (string)$name
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND c.classification = :tetheredClassification';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
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
                      ch.name, ch.contentstreamidentifier, ch.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node p
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                          ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                          ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_node c
                          ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.contentstreamidentifier = ph.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE p.nodeaggregateidentifier = :parentNodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeName $nodeName
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     * @throws DBALException
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
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
                      WHERE n.nodeaggregateidentifier = :parentNodeAggregateIdentifier
                      AND n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                      AND h.name = :nodeName';
        $parameters = [
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
            'contentStreamIdentifier' => (string) $contentStreamIdentifier,
            'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
            'nodeName' => (string) $nodeName
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
     * @return ContentSubgraph[]
     * @internal only used for {@see DoctrineDbalContentGraphProjection}
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }
}
