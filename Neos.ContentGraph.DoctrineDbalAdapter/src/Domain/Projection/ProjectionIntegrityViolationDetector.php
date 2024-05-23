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

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectorInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;

/**
 * The Doctrine database backend implementation for projection invariant checks
 *
 * @internal
 */
final class ProjectionIntegrityViolationDetector implements ProjectionIntegrityViolationDetectorInterface
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly ContentGraphTableNames $tableNames,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hierarchyIntegrityIsProvided(): Result
    {
        $result = new Result();

        $disconnectedHierarchyRelationRecords = $this->dbal->executeQuery(
            'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
                LEFT JOIN ' . $this->tableNames->node() . ' p ON h.parentnodeanchor = p.relationanchorpoint
                LEFT JOIN ' . $this->tableNames->node() . ' c ON h.childnodeanchor = c.relationanchorpoint
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

        $invalidlyHashedHierarchyRelationRecords = $this->dbal->executeQuery(
            'SELECT * FROM ' . $this->tableNames->hierarchyRelation() . ' h LEFT JOIN ' . $this->tableNames->dimensionSpacePoints() . ' dsp ON dsp.hash = h.dimensionspacepointhash
                HAVING dsp.dimensionspacepoint IS NULL'
        )->fetchAllAssociative();

        foreach ($invalidlyHashedHierarchyRelationRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' has an invalid dimension space point hash.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        $hierarchyRelationRecordsAppearingMultipleTimes = $this->dbal->executeQuery(
            'SELECT COUNT(*) as uniquenessCounter, h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
                LEFT JOIN ' . $this->tableNames->node() . ' p ON h.parentnodeanchor = p.relationanchorpoint
                LEFT JOIN ' . $this->tableNames->node() . ' c ON h.childnodeanchor = c.relationanchorpoint
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

        $ambiguouslySortedHierarchyRelationRecords = $this->dbal->executeQuery(
            'SELECT *, COUNT(position)
                    FROM ' . $this->tableNames->hierarchyRelation() . '
                    GROUP BY position, parentnodeanchor, contentstreamid, dimensionspacepointhash
                    HAVING COUNT(position) > 1'
        );

        if ($ambiguouslySortedHierarchyRelationRecords->columnCount() === 0) {
            return $result;
        }

        $dimensionSpacePoints = $this->findProjectedDimensionSpacePoints();

        foreach ($ambiguouslySortedHierarchyRelationRecords as $hierarchyRelationRecord) {
            $ambiguouslySortedNodeRecords = $this->dbal->executeQuery(
                'SELECT nodeaggregateid
                    FROM ' . $this->tableNames->node() . '
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
        $unnamedTetheredNodeRecords = $this->dbal->executeQuery(
            'SELECT n.nodeaggregateid, h.contentstreamid
                    FROM ' . $this->tableNames->node() . ' n
                INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
                    ON h.childnodeanchor = n.relationanchorpoint
                WHERE n.classification = :tethered
              AND n.name IS NULL
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
    public function subtreeTagsAreInherited(): Result
    {
        $result = new Result();

        // NOTE:
        // This part determines if a parent hierarchy relation contains subtree tags that are not existing in the child relation.
        // This could probably be solved with JSON_ARRAY_INTERSECT(JSON_KEYS(ph.subtreetags), JSON_KEYS(h.subtreetags) but unfortunately that's only available with MariaDB 11.2+ according to https://mariadb.com/kb/en/json_array_intersect/
        $hierarchyRelationsWithMissingSubtreeTags = $this->dbal->executeQuery(
            'SELECT
              ph.*
            FROM
              ' . $this->tableNames->hierarchyRelation() . ' h
              INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' ph
                ON ph.childnodeanchor = h.parentnodeanchor
                AND ph.contentstreamid = h.contentstreamid
                AND ph.dimensionspacepointhash = h.dimensionspacepointhash
            WHERE
              EXISTS (
                SELECT t.tag FROM JSON_TABLE(JSON_KEYS(ph.subtreetags), \'$[*]\' COLUMNS(tag VARCHAR(30) PATH \'$\')) t WHERE NOT JSON_EXISTS(h.subtreetags, CONCAT(\'$.\', t.tag))
              )'
        )->fetchAllAssociative();

        foreach ($hierarchyRelationsWithMissingSubtreeTags as $hierarchyRelation) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($hierarchyRelation, JSON_THROW_ON_ERROR)
                . ' is missing inherited subtree tags from the parent relation.',
                self::ERROR_CODE_NODE_HAS_MISSING_SUBTREE_TAG
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

        $referenceRelationRecordsDetachedFromSource = $this->dbal->executeQuery(
            'SELECT * FROM ' . $this->tableNames->referenceRelation() . '
                WHERE nodeanchorpoint NOT IN (
                    SELECT relationanchorpoint FROM ' . $this->tableNames->node() . '
                )'
        )->fetchAllAssociative();

        foreach ($referenceRelationRecordsDetachedFromSource as $record) {
            $result->addError(new Error(
                'Reference relation ' . \json_encode($record)
                . ' is detached from its origin.',
                self::ERROR_CODE_REFERENCE_INTEGRITY_IS_COMPROMISED
            ));
        }

        $referenceRelationRecordsWithInvalidTarget = $this->dbal->executeQuery(
            'SELECT sh.contentstreamid AS contentstreamId,
                    s.nodeaggregateid AS sourceNodeAggregateId,
                    r.destinationnodeaggregateid AS destinationNodeAggregateId
                FROM ' . $this->tableNames->referenceRelation() . ' r
                INNER JOIN ' . $this->tableNames->node() . ' s ON r.nodeanchorpoint = s.relationanchorpoint
                INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' sh
                    ON r.nodeanchorpoint = sh.childnodeanchor
                LEFT JOIN (
                    ' . $this->tableNames->node() . ' d
                    INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' dh
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
                $nodeAggregateIdsInCycles = $this->dbal->executeQuery(
                    'WITH RECURSIVE subgraph AS (
    SELECT
     	h.childnodeanchor
    FROM
        ' . $this->tableNames->hierarchyRelation() . ' h
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
	 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
        on h.parentnodeanchor = p.childnodeanchor
	 WHERE
	 	h.contentstreamid = :contentStreamId
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
)
SELECT nodeaggregateid FROM ' . $this->tableNames->node() . ' n
INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
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
                $ambiguousNodeAggregateRecords = $this->dbal->executeQuery(
                    'SELECT n.nodeaggregateid, COUNT(n.relationanchorpoint)
                    FROM ' . $this->tableNames->node() . ' n
                    INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
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
                $nodeRecordsWithMultipleParents = $this->dbal->executeQuery(
                    'SELECT c.nodeaggregateid
                    FROM ' . $this->tableNames->node() . ' c
                    INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
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
                $nodeAggregateRecords = $this->dbal->executeQuery(
                    'SELECT DISTINCT n.nodetypename FROM ' . $this->tableNames->node() . ' n
                        INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
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
                $nodeAggregateRecords = $this->dbal->executeQuery(
                    'SELECT DISTINCT n.classification FROM ' . $this->tableNames->node() . ' n
                        INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
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
            $excessivelyCoveringNodeRecords = $this->dbal->executeQuery(
                'SELECT n.nodeaggregateid, c.dimensionspacepointhash
                    FROM ' . $this->tableNames->hierarchyRelation() . ' c
                    INNER JOIN ' . $this->tableNames->node() . ' n
                        ON c.childnodeanchor = n.relationanchorpoint
                    LEFT JOIN ' . $this->tableNames->hierarchyRelation() . ' p
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
            $nodeRecordsWithMissingOriginCoverage = $this->dbal->executeQuery(
                'SELECT nodeaggregateid, origindimensionspacepointhash
                    FROM ' . $this->tableNames->node() . ' n
                    INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
                        ON h.childnodeanchor = n.relationanchorpoint
                    WHERE
                        h.contentstreamid = :contentStreamId
                    AND nodeaggregateid NOT IN (
                        -- this query finds all nodes whose origin *IS COVERED* by an incoming hierarchy relation.
                        SELECT n.nodeaggregateid
                        FROM ' . $this->tableNames->node() . ' n
                        LEFT JOIN ' . $this->tableNames->hierarchyRelation() . ' p
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
        $rows = $this->dbal->executeQuery(
            'SELECT DISTINCT contentstreamid FROM ' . $this->tableNames->hierarchyRelation()
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
        $records = $this->dbal->executeQuery(
            'SELECT dimensionspacepoint FROM ' . $this->tableNames->dimensionSpacePoints()
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
        $records = $this->dbal->executeQuery(
            'SELECT
                DISTINCT nodeaggregateid
            FROM
                ' . $this->tableNames->node() . '
                INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
                ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId',
            [
                'contentStreamId' => $contentStreamId->value,
            ]
        )->fetchAllAssociative();

        return array_map(function (array $record) {
            return NodeAggregateId::fromString($record['nodeaggregateid']);
        }, $records);
    }
}
