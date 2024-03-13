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

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectorInterface;

/**
 * The Doctrine database backend implementation for projection invariant checks
 *
 * @internal
 */
final class ProjectionIntegrityViolationDetector implements ProjectionIntegrityViolationDetectorInterface
{
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly string $tableNamePrefix
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hierarchyIntegrityIsProvided(): Result
    {
        $result = new Result();

        $disconnectedHierarchyRelationRecords = $this->client->getConnection()->executeQuery(
            'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                LEFT JOIN ' . $this->tableNamePrefix . '_node p ON h.parentnodeanchor = p.relationanchorpoint
                LEFT JOIN ' . $this->tableNamePrefix . '_node c ON h.childnodeanchor = c.relationanchorpoint
                WHERE h.parentnodeanchor != :rootNodeAnchor
                AND (
                    p.relationanchorpoint IS NULL
                    OR c.relationanchorpoint IS NULL
                )',
            [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
            ]
        );

        foreach ($disconnectedHierarchyRelationRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' is disconnected.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        $invalidlyHashedHierarchyRelationRecords = $this->client->getConnection()->executeQuery(
            'SELECT * FROM ' . $this->tableNamePrefix . '_hierarchyrelation h LEFT JOIN ' . $this->tableNamePrefix . '_dimensionspacepoints dsp ON dsp.hash = h.dimensionspacepointhash
                HAVING dsp.dimensionspacepoint IS NULL'
        )->fetchAllAssociative();

        foreach ($invalidlyHashedHierarchyRelationRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' has an invalid dimension space point hash.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        $hierarchyRelationRecordsAppearingMultipleTimes = $this->client->getConnection()->executeQuery(
            'SELECT COUNT(*) as uniquenessCounter, h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                LEFT JOIN ' . $this->tableNamePrefix . '_node p ON h.parentnodeanchor = p.relationanchorpoint
                LEFT JOIN ' . $this->tableNamePrefix . '_node c ON h.childnodeanchor = c.relationanchorpoint
                WHERE h.parentnodeanchor != :rootNodeAnchor
                GROUP BY p.nodeaggregateid, c.nodeaggregateid,
                         h.dimensionspacepointhash, h.contentstreamid
                HAVING uniquenessCounter > 1
                ',
            [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
            ]
        )->fetchAllAssociative();

        foreach ($hierarchyRelationRecordsAppearingMultipleTimes as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' appears multiple times.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function siblingsAreDistinctlySorted(): Result
    {
        $result = new Result();

        $ambiguouslySortedHierarchyRelationRecords = $this->client->getConnection()->executeQuery(
            'SELECT *, COUNT(position)
                    FROM ' . $this->tableNamePrefix . '_hierarchyrelation
                    GROUP BY position, parentnodeanchor, contentstreamid, dimensionspacepointhash
                    HAVING COUNT(position) > 1'
        );

        if (empty($ambiguouslySortedHierarchyRelationRecords)) {
            return $result;
        }

        $dimensionSpacePoints = $this->findProjectedDimensionSpacePoints();

        foreach ($ambiguouslySortedHierarchyRelationRecords as $hierarchyRelationRecord) {
            $ambiguouslySortedNodeRecords = $this->client->getConnection()->executeQuery(
                'SELECT nodeaggregateid
                    FROM ' . $this->tableNamePrefix . '_node
                    WHERE relationanchorpoint = :relationAnchorPoint',
                [
                    'relationAnchorPoint' => $hierarchyRelationRecord['childnodeanchor']
                ]
            )->fetchAllAssociative();

            $result->addError(new Error(
                'Siblings ' . implode(', ', array_map(function (array $record) {
                    return $record['nodeaggregateid'];
                }, $ambiguouslySortedNodeRecords))
                . ' are ambiguously sorted in content stream ' . $hierarchyRelationRecord['contentstreamid']
                . ' and dimension space point ' . $dimensionSpacePoints[$hierarchyRelationRecord['dimensionspacepointhash']]?->toJson(),
                self::ERROR_CODE_SIBLINGS_ARE_AMBIGUOUSLY_SORTED
            ));
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function tetheredNodesAreNamed(): Result
    {
        $result = new Result();
        $unnamedTetheredNodeRecords = $this->client->getConnection()->executeQuery(
            'SELECT n.nodeaggregateid, h.contentstreamid
                    FROM ' . $this->tableNamePrefix . '_node n
                INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                    ON h.childnodeanchor = n.relationanchorpoint
                WHERE n.classification = :tethered
              AND h.name IS NULL
              GROUP BY n.nodeaggregateid, h.contentstreamid',
            [
                'tethered' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
            ]
        )->fetchAllAssociative();

        foreach ($unnamedTetheredNodeRecords as $unnamedTetheredNodeRecord) {
            $result->addError(new Error(
                'Node aggregate ' . $unnamedTetheredNodeRecord['nodeaggregateid']
                . ' is unnamed in content stream ' . $unnamedTetheredNodeRecord['contentstreamid'] . '.',
                self::ERROR_CODE_TETHERED_NODE_IS_UNNAMED
            ));
        }

        return $result;
    }


    /**
     * @inheritDoc
     */
    public function restrictionsArePropagatedRecursively(): Result
    {
        $result = new Result();
        $nodeRecordsWithMissingRestrictions = $this->client->getConnection()->executeQuery(
            'SELECT c.nodeaggregateid, h.contentstreamid, h.dimensionspacepointhash
            FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
            INNER JOIN ' . $this->tableNamePrefix . '_node p
                ON p.relationanchorpoint = h.parentnodeanchor
            INNER JOIN ' . $this->tableNamePrefix . '_restrictionrelation pr
                ON pr.affectednodeaggregateid = p.nodeaggregateid
                AND pr.contentstreamid = h.contentstreamid
                AND pr.dimensionspacepointhash = h.dimensionspacepointhash
            INNER JOIN ' . $this->tableNamePrefix . '_node c
                ON c.relationanchorpoint = h.childnodeanchor
            LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation cr
                ON cr.affectednodeaggregateid = c.nodeaggregateid
                AND cr.contentstreamid = h.contentstreamid
                AND cr.dimensionspacepointhash = h.dimensionspacepointhash
            WHERE cr.affectednodeaggregateid IS NULL'
        )->fetchAllAssociative();

        foreach ($nodeRecordsWithMissingRestrictions as $nodeRecord) {
            $result->addError(new Error(
                'Node aggregate ' . $nodeRecord['nodeaggregateid']
                . ' misses a restriction relation in content stream ' . $nodeRecord['contentstreamid']
                . ' and dimension space point hash ' . $nodeRecord['dimensionspacepointhash'],
                self::ERROR_CODE_NODE_HAS_MISSING_RESTRICTION
            ));
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function restrictionIntegrityIsProvided(): Result
    {
        $result = new Result();

        $restrictionRelationRecordsWithoutOriginOrAffectedNode = $this->client->getConnection()->executeQuery(
            '
            SELECT r.* FROM ' . $this->tableNamePrefix . '_restrictionrelation r
                LEFT JOIN (
                    ' . $this->tableNamePrefix . '_node p
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                        ON p.relationanchorpoint = ph.childnodeanchor
                ) ON p.nodeaggregateid = r.originnodeaggregateid
                AND ph.contentstreamid = r.contentstreamid
                AND ph.dimensionspacepointhash = r.dimensionspacepointhash
                LEFT JOIN (
                    ' . $this->tableNamePrefix . '_node c
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                        ON c.relationanchorpoint = ch.childnodeanchor
                ) ON c.nodeaggregateid = r.affectednodeaggregateid
                AND ch.contentstreamid = r.contentstreamid
                AND ch.dimensionspacepointhash = r.dimensionspacepointhash
            WHERE p.nodeaggregateid IS NULL
            OR c.nodeaggregateid IS NULL'
        )->fetchAllAssociative();

        foreach ($restrictionRelationRecordsWithoutOriginOrAffectedNode as $relationRecord) {
            $result->addError(new Error(
                'Restriction relation ' . $relationRecord['originnodeaggregateid']
                . ' -> ' . $relationRecord['affectednodeaggregateid']
                . ' does not connect two nodes in content stream ' . $relationRecord['contentstreamid']
                . ' and dimension space point ' . $relationRecord['dimensionspacepointhash'],
                self::ERROR_CODE_RESTRICTION_INTEGRITY_IS_COMPROMISED
            ));
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function referenceIntegrityIsProvided(): Result
    {
        $result = new Result();

        $referenceRelationRecordsDetachedFromSource = $this->client->getConnection()->executeQuery(
            'SELECT * FROM ' . $this->tableNamePrefix . '_referencerelation
                WHERE nodeanchorpoint NOT IN (
                    SELECT relationanchorpoint FROM ' . $this->tableNamePrefix . '_node
                )'
        )->fetchAllAssociative();

        foreach ($referenceRelationRecordsDetachedFromSource as $record) {
            $result->addError(new Error(
                'Reference relation ' . \json_encode($record)
                . ' is detached from its origin.',
                self::ERROR_CODE_REFERENCE_INTEGRITY_IS_COMPROMISED
            ));
        }

        $referenceRelationRecordsWithInvalidTarget = $this->client->getConnection()->executeQuery(
            'SELECT sh.contentstreamid AS contentstreamId,
                    s.nodeaggregateid AS sourceNodeAggregateId,
                    r.destinationnodeaggregateid AS destinationNodeAggregateId
                FROM ' . $this->tableNamePrefix . '_referencerelation r
                INNER JOIN ' . $this->tableNamePrefix . '_node s ON r.nodeanchorpoint = s.relationanchorpoint
                INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation sh
                    ON r.nodeanchorpoint = sh.childnodeanchor
                LEFT JOIN (
                    ' . $this->tableNamePrefix . '_node d
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation dh
                        ON d.relationanchorpoint = dh.childnodeanchor
                ) ON r.destinationnodeaggregateid = d.nodeaggregateid
                    AND sh.contentstreamid = dh.contentstreamid
                    AND sh.dimensionspacepointhash = dh.dimensionspacepointhash
                WHERE d.nodeaggregateid IS NULL
                GROUP BY s.nodeaggregateid'
        )->fetchAllAssociative();

        foreach ($referenceRelationRecordsWithInvalidTarget as $record) {
            $result->addError(new Error(
                'Destination node aggregate ' . $record['destinationNodeAggregateId']
                . ' does not cover any dimension space points the source ' . $record['sourceNodeAggregateId']
                . ' does in content stream ' . $record['contentstreamId'],
                self::ERROR_CODE_REFERENCE_INTEGRITY_IS_COMPROMISED
            ));
        }

        return $result;
    }

    /**
     * This is provided by the database structure:
     * reference relations with the same source and same name must have distinct positions
     * @inheritDoc
     */
    public function referencesAreDistinctlySorted(): Result
    {
        return new Result();
    }

    /**
     * @inheritDoc
     */
    public function allNodesAreConnectedToARootNodePerSubgraph(): Result
    {
        $result = new Result();

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $nodeAggregateIdsInCycles = $this->client->getConnection()->executeQuery(
                    'WITH RECURSIVE subgraph AS (
    SELECT
     	h.childnodeanchor
    FROM
        ' . $this->tableNamePrefix . '_hierarchyrelation h
    WHERE
        h.parentnodeanchor = :rootAnchorPoint
        AND h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
    UNION
     -- --------------------------------
     -- RECURSIVE query: do one "child" query step
     -- --------------------------------
     SELECT
        h.childnodeanchor
     FROM
        subgraph p
	 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
        on h.parentnodeanchor = p.childnodeanchor
	 WHERE
	 	h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
)
SELECT nodeaggregateid FROM ' . $this->tableNamePrefix . '_node n
INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
    ON h.childnodeanchor = n.relationanchorpoint
WHERE
    h.contentstreamid = :contentStreamId
	AND h.dimensionspacepointhash = :dimensionSpacePointHash
    AND relationanchorpoint NOT IN (SELECT * FROM subgraph)',
                    [
                        'rootAnchorPoint' => NodeRelationAnchorPoint::forRootEdge()->value,
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAllAssociative();

                if (!empty($nodeAggregateIdsInCycles)) {
                    $nodeAggregateIdsInCycles = array_map(function (array $record) {
                        return $record['nodeaggregateid'];
                    }, $nodeAggregateIdsInCycles);

                    $result->addError(new Error(
                        'Subgraph defined by content strean ' . $contentStreamId->value
                        . ' and dimension space point ' . $dimensionSpacePoint->toJson()
                        . ' is cyclic for node aggregates '
                        . implode(',', $nodeAggregateIdsInCycles),
                        self::ERROR_CODE_NODE_IS_DISCONNECTED_FROM_THE_ROOT
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * There are two cases here:
     * a) The node has no ingoing hierarchy relations -> covered by allNodesCoverTheirOrigin
     * b) The node's ingoing hierarchy edges are detached from their parent -> covered by hierarchyIntegrityIsProvided
     * @inheritDoc
     */
    public function nonRootNodesHaveParents(): Result
    {
        return new Result();
    }

    /**
     * @inheritDoc
     */
    public function nodeAggregateIdsAreUniquePerSubgraph(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $ambiguousNodeAggregateRecords = $this->client->getConnection()->executeQuery(
                    'SELECT n.nodeaggregateid, COUNT(n.relationanchorpoint)
                    FROM ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                    WHERE h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    GROUP BY n.nodeaggregateid
                    HAVING COUNT(DISTINCT(n.relationanchorpoint)) > 1',
                    [
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAllAssociative();

                foreach ($ambiguousNodeAggregateRecords as $ambiguousRecord) {
                    $result->addError(new Error(
                        'Node aggregate ' . $ambiguousRecord['nodeaggregateid']
                        . ' is ambiguous in content stream ' . $contentStreamId->value
                        . ' and dimension space point ' . $dimensionSpacePoint->toJson(),
                        self::ERROR_CODE_AMBIGUOUS_NODE_AGGREGATE_IN_SUBGRAPH
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function allNodesHaveAtMostOneParentPerSubgraph(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $nodeRecordsWithMultipleParents = $this->client->getConnection()->executeQuery(
                    'SELECT c.nodeaggregateid
                    FROM ' . $this->tableNamePrefix . '_node c
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = c.relationanchorpoint
                    WHERE h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    GROUP BY c.relationanchorpoint
                    HAVING COUNT(DISTINCT(h.parentnodeanchor)) > 1',
                    [
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAllAssociative();

                foreach ($nodeRecordsWithMultipleParents as $record) {
                    $result->addError(new Error(
                        'Node aggregate ' . $record['nodeaggregateid']
                        . ' has multiple parents in content stream ' . $contentStreamId->value
                        . ' and dimension space point ' . $dimensionSpacePoint->toJson(),
                        self::ERROR_CODE_NODE_HAS_MULTIPLE_PARENTS
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function nodeAggregatesAreConsistentlyTypedPerContentStream(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach (
                $this->findProjectedNodeAggregateIdsInContentStream(
                    $contentStreamId
                ) as $nodeAggregateId
            ) {
                $nodeAggregateRecords = $this->client->getConnection()->executeQuery(
                    'SELECT DISTINCT n.nodetypename FROM ' . $this->tableNamePrefix . '_node n
                        INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                        WHERE h.contentstreamid = :contentStreamId
                        AND n.nodeaggregateid = :nodeAggregateId',
                    [
                        'contentStreamId' => $contentStreamId->value,
                        'nodeAggregateId' => $nodeAggregateId->value
                    ]
                )->fetchAllAssociative();

                if (count($nodeAggregateRecords) > 1) {
                    $result->addError(new Error(
                        'Node aggregate ' . $nodeAggregateId->value
                        . ' in content stream ' . $contentStreamId->value
                        . ' is of ambiguous type ("' . implode('","', array_map(
                            function (array $record) {
                                return $record['nodetypename'];
                            },
                            $nodeAggregateRecords
                        )) . '")',
                        self::ERROR_CODE_NODE_AGGREGATE_IS_AMBIGUOUSLY_TYPED
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function nodeAggregatesAreConsistentlyClassifiedPerContentStream(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach (
                $this->findProjectedNodeAggregateIdsInContentStream(
                    $contentStreamId
                ) as $nodeAggregateId
            ) {
                $nodeAggregateRecords = $this->client->getConnection()->executeQuery(
                    'SELECT DISTINCT n.classification FROM ' . $this->tableNamePrefix . '_node n
                        INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                        WHERE h.contentstreamid = :contentStreamId
                        AND n.nodeaggregateid = :nodeAggregateId',
                    [
                        'contentStreamId' => $contentStreamId->value,
                        'nodeAggregateId' => $nodeAggregateId->value
                    ]
                )->fetchAllAssociative();

                if (count($nodeAggregateRecords) > 1) {
                    $result->addError(new Error(
                        'Node aggregate ' . $nodeAggregateId->value
                        . ' in content stream ' . $contentStreamId->value
                        . ' is ambiguously classified ("' . implode('","', array_map(
                            function (array $record) {
                                return $record['classification'];
                            },
                            $nodeAggregateRecords
                        )) . '")',
                        self::ERROR_CODE_NODE_AGGREGATE_IS_AMBIGUOUSLY_CLASSIFIED
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function childNodeCoverageIsASubsetOfParentNodeCoverage(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            $excessivelyCoveringNodeRecords = $this->client->getConnection()->executeQuery(
                'SELECT n.nodeaggregateid, c.dimensionspacepointhash
                    FROM ' . $this->tableNamePrefix . '_hierarchyrelation c
                    INNER JOIN ' . $this->tableNamePrefix . '_node n
                        ON c.childnodeanchor = n.relationanchorpoint
                    LEFT JOIN ' . $this->tableNamePrefix . '_hierarchyrelation p
                        ON c.parentnodeanchor = p.childnodeanchor
                    WHERE c.contentstreamid = :contentStreamId
                    AND p.contentstreamid = :contentStreamId
                    AND c.dimensionspacepointhash = p.dimensionspacepointhash
                    AND p.childnodeanchor IS NULL',
                [
                    'contentStreamId' => $contentStreamId->value
                ]
            )->fetchAllAssociative();

            foreach ($excessivelyCoveringNodeRecords as $excessivelyCoveringNodeRecord) {
                $result->addError(new Error(
                    'Node aggregate ' . $excessivelyCoveringNodeRecord['nodeaggregateid']
                    . ' in content stream ' . $contentStreamId->value
                    . ' covers dimension space point hash ' . $excessivelyCoveringNodeRecord['dimensionspacepointhash']
                    . ' but its parent does not.',
                    self::ERROR_CODE_CHILD_NODE_COVERAGE_IS_NO_SUBSET_OF_PARENT_NODE_COVERAGE
                ));
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function allNodesCoverTheirOrigin(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            $nodeRecordsWithMissingOriginCoverage = $this->client->getConnection()->executeQuery(
                'SELECT nodeaggregateid, origindimensionspacepointhash
                    FROM ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                    WHERE
                        h.contentstreamid = :contentStreamId
                    AND nodeaggregateid NOT IN (
                        -- this query finds all nodes whose origin *IS COVERED* by an incoming hierarchy relation.
                        SELECT n.nodeaggregateid
                        FROM ' . $this->tableNamePrefix . '_node n
                        LEFT JOIN ' . $this->tableNamePrefix . '_hierarchyrelation p
                            ON p.childnodeanchor = n.relationanchorpoint
                            AND p.dimensionspacepointhash = n.origindimensionspacepointhash
                            WHERE p.contentstreamid = :contentStreamId
                    )
                    AND classification != :rootClassification',
                [
                    'contentStreamId' => $contentStreamId->value,
                    'rootClassification' => NodeAggregateClassification::CLASSIFICATION_ROOT->value
                ]
            )->fetchAllAssociative();

            foreach ($nodeRecordsWithMissingOriginCoverage as $nodeRecord) {
                $result->addError(new Error(
                    'Node aggregate ' . $nodeRecord['nodeaggregateid']
                    . ' in content stream ' . $contentStreamId->value
                    . ' does not cover its origin dimension space point hash ' . $nodeRecord['origindimensionspacepointhash']
                    . '.',
                    self::ERROR_CODE_NODE_DOES_NOT_COVER_ITS_ORIGIN
                ));
            }
        }

        return $result;
    }

    /**
     * Returns all content stream ids
     *
     * @return iterable<ContentStreamId>
     */
    protected function findProjectedContentStreamIds(): iterable
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery(
            'SELECT DISTINCT contentstreamid FROM ' . $this->tableNamePrefix . '_hierarchyrelation'
        )->fetchAllAssociative();

        return array_map(function (array $row) {
            return ContentStreamId::fromString($row['contentstreamid']);
        }, $rows);
    }

    /**
     * Returns all projected dimension space points
     *
     * @return DimensionSpacePointSet
     */
    protected function findProjectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        $records = $this->client->getConnection()->executeQuery(
            'SELECT dimensionspacepoint FROM ' . $this->tableNamePrefix . '_dimensionspacepoints'
        )->fetchAllAssociative();

        $records = array_map(function (array $record) {
            return DimensionSpacePoint::fromJsonString($record['dimensionspacepoint']);
        }, $records);

        return new DimensionSpacePointSet($records);
    }

    /**
     * @return array<int,NodeAggregateId>
     * @throws \Doctrine\DBAL\Exception | \Doctrine\DBAL\Driver\Exception
     */
    protected function findProjectedNodeAggregateIdsInContentStream(
        ContentStreamId $contentStreamId
    ): array {
        $records = $this->client->getConnection()->executeQuery(
            'SELECT DISTINCT nodeaggregateid FROM ' . $this->tableNamePrefix . '_node'
        )->fetchAllAssociative();

        return array_map(function (array $record) {
            return NodeAggregateId::fromString($record['nodeaggregateid']);
        }, $records);
    }
}
