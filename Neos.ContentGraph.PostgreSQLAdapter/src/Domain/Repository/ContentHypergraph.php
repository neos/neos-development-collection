<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Dto\DescendantAssignment;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Dto\DescendantAssignments;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * The PostgreSQL adapter content hypergraph
 *
 * To be used as a read-only source of subhypergraphs, node aggregates and nodes
 *
 * @internal but the parent {@see ContentGraphInterface} is API
 */
final class ContentHypergraph implements ContentGraphInterface
{
    private PostgresDbalClientInterface $databaseClient;

    private NodeFactory $nodeFactory;

    /**
     * @var array|ContentSubhypergraph[]
     */
    private array $subhypergraphs;

    public function __construct(
        PostgresDbalClientInterface $databaseClient,
        NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
        $this->databaseClient = $databaseClient;
        $this->nodeFactory = $nodeFactory;
    }

    public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamId . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subhypergraphs[$index])) {
            $this->subhypergraphs[$index] = new ContentSubhypergraph(
                $contentStreamId,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->databaseClient,
                $this->nodeFactory,
                $this->nodeTypeManager,
                $this->tableNamePrefix
            );
        }

        return $this->subhypergraphs[$index];
    }

    public function findNodeByIdAndOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?Node {
        $query = HypergraphQuery::create($contentStreamId, $this->tableNamePrefix);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateId);

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            VisibilityConstraints::withoutRestrictions(),
            $originDimensionSpacePoint->toDimensionSpacePoint()
        ) : null;
    }

    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        throw new \BadMethodCallException('method findRootNodeAggregateByType is not implemented yet.', 1645782874);
    }

    /**
     * @return \Iterator<int,NodeAggregate>
     */
    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): \Iterator {
        return new \Generator();
    }

    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $query = HypergraphQuery::create($contentStreamId, $this->tableNamePrefix, true);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateId);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */ '
            SELECT n.origindimensionspacepoint, n.nodeaggregateidentifier, n.nodetypename,
                   n.classification, n.properties, n.nodename, ph.contentstreamidentifier, ph.dimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation ph
                JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(ph.childnodeanchors)
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier
                AND n.nodeaggregateidentifier = (
                    SELECT pn.nodeaggregateidentifier
                        FROM ' . $this->tableNamePrefix . '_node pn
                        JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation ch
                            ON pn.relationanchorpoint = ch.parentnodeanchor
                        JOIN ' . $this->tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    WHERE cn.nodeaggregateidentifier = :childNodeAggregateIdentifier
                        AND cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.contentstreamidentifier = :contentStreamIdentifier
                )';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamId,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateId,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash
        ];

        $nodeRows = $this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        DimensionSpacePoint $childDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */ '
            SELECT n.origindimensionspacepoint, n.nodeaggregateidentifier, n.nodetypename,
                   n.classification, n.properties, n.nodename, ph.contentstreamidentifier, ph.dimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation ph
                JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(ph.childnodeanchors)
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier
                AND n.nodeaggregateidentifier = (
                    SELECT pn.nodeaggregateidentifier
                        FROM ' . $this->tableNamePrefix . '_node pn
                        JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation ch
                            ON pn.relationanchorpoint = ch.parentnodeanchor
                        JOIN ' . $this->tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    WHERE cn.nodeaggregateidentifier = :childNodeAggregateIdentifier
                        AND ch.dimensionspacepointhash = :childDimensionSpacePointHash
                        AND ch.contentstreamidentifier = :contentStreamIdentifier
                )';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamId,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateId,
            'childDimensionSpacePointHash' => $childDimensionSpacePoint->hash
        ];

        $nodeRows = $this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }


    /**
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $query = HypergraphParentQuery::create($contentStreamId, $this->tableNamePrefix);
        $query = $query->withChildNodeAggregateIdentifier($childNodeAggregateId);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );
        $query = $query->withChildNodeName($name);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );
        $query = $query->withOnlyTethered();

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows, VisibilityConstraints::withoutRestrictions());
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamId $contentStreamId,
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix,
            ['ch.dimensionspacepoint, ch.dimensionspacepointhash']
        );
        $query = $query->withChildNodeName($nodeName)
            ->withOriginDimensionSpacePoint($parentNodeOriginDimensionSpacePoint)
            ->withDimensionSpacePoints($dimensionSpacePointsToCheck);

        $occupiedDimensionSpacePoints = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $row) {
            $occupiedDimensionSpacePoints[$row['dimensionspacepointhash']]
                = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($occupiedDimensionSpacePoints);
    }

    public function findDescendantAssignmentsForCoverageIncrease(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $affectedCoveredDimensionSpacePoints
    ): DescendantAssignments {
        $assignmentRecords = $this->getDatabaseConnection()->executeQuery('
            /**
             * This provides a list of minimal hierarchy relation data to be copied: parent and child node anchors
             * as well as their child node aggregate identifier to help determining the new siblings
             * in the target dimension space point
             */
            WITH RECURSIVE descendantRelations(childnodeanchor, parentnodeaggregateid, parentorigindimensionspacepoint, childnodeaggregateid, childorigindimensionspacepoint, siblingnodeaggregateids) AS (
                /**
                 * Initial query: find the node aggregate identifiers for the node,
                 * its parent and its succeeding siblings, if any
                 * in the dimension space point where the coverage is to be increased FROM
                 */
                SELECT
                       src.relationanchorpoint AS childnodeanchor,
                       srcp.nodeaggregateidentifier AS parentnodeaggregateid,
                       srcp.origindimensionspacepoint AS parentorigindimensionspacepoint,
                       src.nodeaggregateidentifier AS nodeaggregateid,
                       src.origindimensionspacepoint AS origindimensionspacepoint,
                       array (
                           SELECT nodeaggregateidentifier FROM ' . $this->getNodeTableName() . '
                           WHERE relationanchorpoint = ANY (srch.childnodeanchors[(array_position(srch.childnodeanchors, src.relationanchorpoint)) + 1:])
                       ) AS siblingnodeaggregateids
                FROM ' . $this->getHierarchyRelationTableName() . ' srch
                    JOIN ' . $this->getNodeTableName() . ' src ON src.relationanchorpoint = ANY (srch.childnodeanchors)
                    JOIN ' . $this->getNodeTableName() . ' srcp ON srcp.relationanchorpoint = srch.parentnodeanchor
                WHERE srch.contentstreamidentifier = :contentStreamId
                    AND srch.dimensionspacepointhash = :sourceDimensionSpacePointHash
                    AND src.nodeaggregateidentifier = :nodeAggregateId
                UNION ALL
                    /**
                     * Iteration query: find all descendant node and sibling node aggregate identifiers
                     * in the dimension space point where the coverage is to be increased FROM.
                     */
                    SELECT
                           c.relationanchorpoint AS childnodeanchor,
                           p.childnodeaggregateid AS parentnodeaggregateid,
                           p.childorigindimensionspacepoint as parentorigindimensionspacepoint,
                           c.nodeaggregateidentifier AS nodeaggregateid,
                           c.origindimensionspacepoint,
                           array (
                               SELECT nodeaggregateidentifier FROM ' . $this->getNodeTableName() . '
                               WHERE relationanchorpoint = ANY (ch.childnodeanchors[(array_position(ch.childnodeanchors, c.relationanchorpoint)) + 1:])
                           ) AS siblingnodeaggregateids
                    FROM descendantRelations p
                             JOIN ' . $this->getHierarchyRelationTableName() . ' ch ON ch.parentnodeanchor = p.childnodeanchor
                             JOIN ' . $this->getNodeTableName() . ' c ON c.relationanchorpoint = ANY(ch.childnodeanchors)
                    WHERE ch.contentstreamidentifier = :contentStreamId
                      AND ch.dimensionspacepointhash = :sourceDimensionSpacePointHash
            ) SELECT dimensionSpacePoints.dimensionspacepoint,

                parentnodeaggregateid,
                parentnode.origindimensionspacepoint AS parentorigindimensionspacepoint,
                parentorigindimensionspacepoint AS sourceparentorigindimensionspacepoint,

                childnodeaggregateid,
                childnode.origindimensionspacepoint AS childorigindimensionspacepoint,
                descendantRelations.childorigindimensionspacepoint AS sourcechildorigindimensionspacepoint,

                succeedingsibling.nodeaggregateidentifier AS succeedingsiblingnodeaggregateid,
                succeedingsibling.origindimensionspacepoint AS succeedingsiblingorigindimensionspacepoint

            FROM descendantRelations
                /**
                 * Here we join the affected dimension space points to extend the fetched hierarchy relation data
                 * by dimensionspacepoint and dimensionspacepointhash
                 */
                JOIN (
                    SELECT unnest(ARRAY[:dimensionSpacePoints]) AS dimensionspacepoint,
                           unnest(ARRAY[:dimensionSpacePointHashes]) AS dimensionspacepointhash
                ) dimensionSpacePoints ON true
                /**
                 * Resolve parent node anchors for each affected dimension space points, may be null
                 */
                LEFT JOIN (
                    SELECT dimensionspacepointhash, nodeaggregateidentifier, origindimensionspacepoint
                    FROM ' . $this->getNodeTableName() . ' tgtp
                        JOIN ' . $this->getHierarchyRelationTableName() . ' tgtph
                        ON tgtp.relationanchorpoint = ANY(tgtph.childnodeanchors)
                    WHERE tgtph.contentstreamidentifier = :contentStreamId
                ) parentnode
                    ON parentnode.dimensionspacepointhash = dimensionSpacePoints.dimensionspacepointhash
                    AND parentnode.nodeaggregateidentifier = parentnodeaggregateid
                /**
                 * Resolve child node anchors for each affected dimension space points, may be null
                 */
                LEFT JOIN (
                    SELECT dimensionspacepointhash, nodeaggregateidentifier, origindimensionspacepoint
                        FROM ' . $this->getNodeTableName() . ' tgt
                    JOIN ' . $this->getHierarchyRelationTableName() . ' tgth
                        ON tgt.relationanchorpoint = ANY(tgth.childnodeanchors)
                    WHERE tgth.contentstreamidentifier = :contentStreamId
                ) childnode
                    ON childnode.dimensionspacepointhash = dimensionSpacePoints.dimensionspacepointhash
                    AND childnode.nodeaggregateidentifier = childnodeaggregateid
                /**
                 * Resolve primary available succeeding sibling node anchors for each affected dimension space points, may be null
                 */
                LEFT JOIN (
                    SELECT dimensionspacepointhash, nodeaggregateidentifier, origindimensionspacepoint
                        FROM ' . $this->getNodeTableName() . ' tgtsib
                            JOIN ' . $this->getHierarchyRelationTableName() . ' tgtsibh
                            ON tgtsib.relationanchorpoint = ANY(tgtsibh.childnodeanchors)
                        WHERE tgtsibh.contentstreamidentifier = :contentStreamId
                ) succeedingsibling
                    ON succeedingsibling.dimensionspacepointhash = dimensionSpacePoints.dimensionspacepointhash
                    AND succeedingsibling.nodeaggregateidentifier IN (
                        SELECT siblingnodeaggregateid
                            FROM unnest(siblingnodeaggregateids)
                            WITH ORDINALITY siblingnodeaggregateid
                            LIMIT 1
                    )
            ',
            [
                'contentStreamId' => (string)$contentStreamId,
                'sourceDimensionSpacePointHash' => $sourceDimensionSpacePoint->hash,
                'nodeAggregateId' => $nodeAggregateId,
                'dimensionSpacePoints' => array_map(
                    fn(DimensionSpacePoint $dimensionSpacePoint):string
                        => json_encode($dimensionSpacePoint, JSON_THROW_ON_ERROR),
                    $affectedCoveredDimensionSpacePoints->points
                ),
                'dimensionSpacePointHashes' => $affectedCoveredDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePoints' => Connection::PARAM_STR_ARRAY,
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAllAssociative();

        return new DescendantAssignments(
            ...array_map(
                fn (array $assignmentRecord):DescendantAssignment => new DescendantAssignment(
                    DimensionSpacePoint::fromJsonString($assignmentRecord['dimensionspacepoint']),
                    NodeAggregateId::fromString($assignmentRecord['parentnodeaggregateid']),
                    $assignmentRecord['parentorigindimensionspacepoint']
                        ? OriginDimensionSpacePoint::fromJsonString($assignmentRecord['parentorigindimensionspacepoint'])
                        : OriginDimensionSpacePoint::fromJsonString($assignmentRecord['sourceparentorigindimensionspacepoint']),
                    NodeAggregateId::fromString('childnodeaggregateid'),
                    $assignmentRecord['childorigindimensionspacepoint']
                        ? OriginDimensionSpacePoint::fromJsonString($assignmentRecord['childorigindimensionspacepoint'])
                        : OriginDimensionSpacePoint::fromJsonString($assignmentRecord['sourcechildorigindimensionspacepoint']),
                    $assignmentRecord['succeedingsiblingnodeaggregateid']
                        ? NodeAggregateId::fromString($assignmentRecord['succeedingsiblingnodeaggregateid'])
                        : null,
                    $assignmentRecord['succeedingsiblingorigindimensionspacepoint']
                        ? OriginDimensionSpacePoint::fromJsonString($assignmentRecord['succeedingsiblingorigindimensionspacepoint'])
                        : null
                ),
                $assignmentRecords
            )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = 'SELECT COUNT(*) FROM ' . $this->getNodeTableName();

        return $this->getDatabaseConnection()->executeQuery($query)->fetchOne();
    }

    private function getNodeTableName(): string
    {
        return $this->tableNamePrefix . '_node';
    }

    private function getHierarchyRelationTableName(): string
    {
        return $this->tableNamePrefix . '_hierarchyhyperrelation';
    }

    /**
     * @return iterable<int,NodeTypeName>
     */
    public function findUsedNodeTypeNames(): iterable
    {
        return [];
    }

    private function getDatabaseConnection(): DatabaseConnection
    {
        return $this->databaseClient->getConnection();
    }
}
