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
use Doctrine\DBAL\Exception as DbalException;
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

    public function hierarchyIntegrityIsProvided(): Result
    {
        $result = new Result();
        $disconnectedHierarchyRelationStatement = <<<SQL
            SELECT h.* FROM {$this->tableNames->hierarchyRelation()} h
            LEFT JOIN {$this->tableNames->node()} p ON h.parentnodeanchor = p.relationanchorpoint
            LEFT JOIN {$this->tableNames->node()} c ON h.childnodeanchor = c.relationanchorpoint
            WHERE h.parentnodeanchor != :rootNodeAnchor
            AND (
                p.relationanchorpoint IS NULL
                OR c.relationanchorpoint IS NULL
            )
        SQL;
        try {
            $disconnectedHierarchyRelationRecords = $this->dbal->executeQuery($disconnectedHierarchyRelationStatement, [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
            ])->fetchAllAssociative();
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load disconnected hierarchy relations: %s', $e->getMessage()), 1716491735, $e);
        }

        foreach ($disconnectedHierarchyRelationRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' is disconnected.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        $invalidlyHashedHierarchyRelationStatement = <<<SQL
            SELECT
                *
            FROM {$this->tableNames->hierarchyRelation()} h
            LEFT JOIN {$this->tableNames->dimensionSpacePoints()} dsp
                ON dsp.hash = h.dimensionspacepointhash
            WHERE dsp.dimensionspacepoint IS NULL
        SQL;
        try {
            $invalidlyHashedHierarchyRelationRecords = $this->dbal->fetchAllAssociative($invalidlyHashedHierarchyRelationStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load invalid hashed hierarchy relations: %s', $e->getMessage()), 1716491994, $e);
        }

        foreach ($invalidlyHashedHierarchyRelationRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' has an invalid dimension space point hash.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        $hierarchyRelationsAppearingMultipleTimesStatement = <<<SQL
            SELECT
                COUNT(*) as uniquenessCounter,
                h.* FROM {$this->tableNames->hierarchyRelation()} h
                LEFT JOIN {$this->tableNames->node()} p ON h.parentnodeanchor = p.relationanchorpoint
                LEFT JOIN {$this->tableNames->node()} c ON h.childnodeanchor = c.relationanchorpoint
            WHERE
                h.parentnodeanchor != :rootNodeAnchor
            GROUP BY
                p.nodeaggregateid, c.nodeaggregateid,
                h.dimensionspacepointhash, h.contentstreamid
            HAVING uniquenessCounter > 1
        SQL;
        try {
            $hierarchyRelationRecordsAppearingMultipleTimes = $this->dbal->fetchAllAssociative($hierarchyRelationsAppearingMultipleTimesStatement, [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load hierarchy relations that appear multiple times: %s', $e->getMessage()), 1716495277, $e);
        }
        foreach ($hierarchyRelationRecordsAppearingMultipleTimes as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' appears multiple times.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        return $result;
    }

    public function siblingsAreDistinctlySorted(): Result
    {
        $result = new Result();

        $ambiguouslySortedHierarchyRelationStatement = <<<SQL
            SELECT
                *,
                COUNT(position)
            FROM
                {$this->tableNames->hierarchyRelation()}
            GROUP BY
                position,
                parentnodeanchor,
                contentstreamid,
                dimensionspacepointhash
            HAVING
                COUNT(position) > 1
        SQL;
        try {
            $ambiguouslySortedHierarchyRelationRecords = $this->dbal->executeQuery($ambiguouslySortedHierarchyRelationStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load ambiguously sorted hierarchy relations: %s', $e->getMessage()), 1716492251, $e);
        }
        if ($ambiguouslySortedHierarchyRelationRecords->columnCount() === 0) {
            return $result;
        }

        $dimensionSpacePoints = $this->findProjectedDimensionSpacePoints();

        $ambiguouslySortedNodesStatement = <<<SQL
            SELECT nodeaggregateid
            FROM {$this->tableNames->node()}
            WHERE relationanchorpoint = :relationAnchorPoint
        SQL;
        foreach ($ambiguouslySortedHierarchyRelationRecords->fetchAllAssociative() as $hierarchyRelationRecord) {
            try {
                $ambiguouslySortedNodeRecords = $this->dbal->fetchAllAssociative($ambiguouslySortedNodesStatement, [
                    'relationAnchorPoint' => $hierarchyRelationRecord['childnodeanchor']
                ]);
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to load ambiguously sorted nodes: %s', $e->getMessage()), 1716492358, $e);
            }

            $result->addError(new Error(
                'Siblings ' . implode(', ', array_map(static fn (array $record) => $record['nodeaggregateid'], $ambiguouslySortedNodeRecords))
                . ' are ambiguously sorted in content stream ' . $hierarchyRelationRecord['contentstreamid']
                . ' and dimension space point ' . $dimensionSpacePoints[$hierarchyRelationRecord['dimensionspacepointhash']]?->toJson(),
                self::ERROR_CODE_SIBLINGS_ARE_AMBIGUOUSLY_SORTED
            ));
        }

        return $result;
    }

    public function tetheredNodesAreNamed(): Result
    {
        $result = new Result();
        $unnamedTetheredNodesStatement = <<<SQL
            SELECT
                n.nodeaggregateid, h.contentstreamid
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.classification = :tethered
                AND n.name IS NULL
            GROUP BY
                n.nodeaggregateid, h.contentstreamid
        SQL;
        try {
            $unnamedTetheredNodeRecords = $this->dbal->fetchAllAssociative($unnamedTetheredNodesStatement, [
                'tethered' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load unnamed tethered nodes: %s', $e->getMessage()), 1716492549, $e);
        }

        foreach ($unnamedTetheredNodeRecords as $unnamedTetheredNodeRecord) {
            $result->addError(new Error(
                'Node aggregate ' . $unnamedTetheredNodeRecord['nodeaggregateid']
                . ' is unnamed in content stream ' . $unnamedTetheredNodeRecord['contentstreamid'] . '.',
                self::ERROR_CODE_TETHERED_NODE_IS_UNNAMED
            ));
        }

        return $result;
    }

    public function subtreeTagsAreInherited(): Result
    {
        $result = new Result();

        // NOTE:
        // This part determines if a parent hierarchy relation contains subtree tags that are not existing in the child relation.
        // This could probably be solved with JSON_ARRAY_INTERSECT(JSON_KEYS(ph.subtreetags), JSON_KEYS(h.subtreetags) but unfortunately that's only available with MariaDB 11.2+ according to https://mariadb.com/kb/en/json_array_intersect/
        $hierarchyRelationsWithMissingSubtreeTagsStatement = <<<SQL
            SELECT
                ph.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
                INNER JOIN {$this->tableNames->hierarchyRelation()} ph
                    ON ph.childnodeanchor = h.parentnodeanchor
                    AND ph.contentstreamid = h.contentstreamid
                    AND ph.dimensionspacepointhash = h.dimensionspacepointhash
            WHERE
                EXISTS (
                    SELECT t.tag FROM JSON_TABLE(JSON_KEYS(ph.subtreetags), '\$[*]' COLUMNS(tag VARCHAR(30) PATH '\$')) t WHERE NOT JSON_EXISTS(h.subtreetags, CONCAT('\$.', t.tag))
                )
        SQL;
        try {
            $hierarchyRelationsWithMissingSubtreeTags = $this->dbal->fetchAllAssociative($hierarchyRelationsWithMissingSubtreeTagsStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load hierarchy relations with missing subtree tags: %s', $e->getMessage()), 1716492658, $e);
        }

        foreach ($hierarchyRelationsWithMissingSubtreeTags as $hierarchyRelation) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($hierarchyRelation)
                . ' is missing inherited subtree tags from the parent relation.',
                self::ERROR_CODE_NODE_HAS_MISSING_SUBTREE_TAG
            ));
        }

        return $result;
    }

    public function referenceIntegrityIsProvided(): Result
    {
        $result = new Result();

        $referenceRelationRecordsDetachedFromSourceStatement = <<<SQL
            SELECT
                *
            FROM
                {$this->tableNames->referenceRelation()}
            WHERE
                nodeanchorpoint NOT IN (
                    SELECT relationanchorpoint FROM {$this->tableNames->node()}
                )
        SQL;
        try {
            $referenceRelationRecordsDetachedFromSource = $this->dbal->fetchAllAssociative($referenceRelationRecordsDetachedFromSourceStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load detached reference relations: %s', $e->getMessage()), 1716492786, $e);
        }

        foreach ($referenceRelationRecordsDetachedFromSource as $record) {
            $result->addError(new Error(
                'Reference relation ' . \json_encode($record)
                . ' is detached from its origin.',
                self::ERROR_CODE_REFERENCE_INTEGRITY_IS_COMPROMISED
            ));
        }

        $referenceRelationRecordsWithInvalidTargetStatement = <<<SQL
            SELECT
                sh.contentstreamid AS contentstreamId,
                s.nodeaggregateid AS sourceNodeAggregateId,
                r.destinationnodeaggregateid AS destinationNodeAggregateId
            FROM
                {$this->tableNames->referenceRelation()} r
                INNER JOIN {$this->tableNames->node()} s ON r.nodeanchorpoint = s.relationanchorpoint
                INNER JOIN {$this->tableNames->hierarchyRelation()} sh ON r.nodeanchorpoint = sh.childnodeanchor
                LEFT JOIN (
                    {$this->tableNames->node()} d
                    INNER JOIN {$this->tableNames->hierarchyRelation()} dh ON d.relationanchorpoint = dh.childnodeanchor
                ) ON r.destinationnodeaggregateid = d.nodeaggregateid
                  AND sh.contentstreamid = dh.contentstreamid
                  AND sh.dimensionspacepointhash = dh.dimensionspacepointhash
                WHERE
                    d.nodeaggregateid IS NULL
                GROUP BY
                    s.nodeaggregateid
        SQL;
        try {
            $referenceRelationRecordsWithInvalidTarget = $this->dbal->fetchAllAssociative($referenceRelationRecordsWithInvalidTargetStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load reference relations with invalid target: %s', $e->getMessage()), 1716492909, $e);
        }

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
     */
    public function referencesAreDistinctlySorted(): Result
    {
        // TODO implement
        return new Result();
    }

    public function allNodesAreConnectedToARootNodePerSubgraph(): Result
    {
        $result = new Result();

        $nodeAggregateIdsInCyclesStatement = <<<SQL
            WITH RECURSIVE subgraph AS (
                SELECT
                    h.childnodeanchor
                FROM
                    {$this->tableNames->hierarchyRelation()} h
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
                 INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    on h.parentnodeanchor = p.childnodeanchor
                 WHERE
                    h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
            )
            SELECT nodeaggregateid FROM {$this->tableNames->node()} n
            INNER JOIN {$this->tableNames->hierarchyRelation()} h
                ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND relationanchorpoint NOT IN (SELECT * FROM subgraph)
        SQL;

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                try {
                    $nodeAggregateIdsInCycles = $this->dbal->fetchFirstColumn($nodeAggregateIdsInCyclesStatement, [
                        'rootAnchorPoint' => NodeRelationAnchorPoint::forRootEdge()->value,
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to load cyclic node relations: %s', $e->getMessage()), 1716493090, $e);
                }

                if (!empty($nodeAggregateIdsInCycles)) {
                    $result->addError(new Error(
                        'Subgraph defined by content stream ' . $contentStreamId->value
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
     */
    public function nonRootNodesHaveParents(): Result
    {
        // TODO implement
        return new Result();
    }

    public function nodeAggregateIdsAreUniquePerSubgraph(): Result
    {
        $result = new Result();
        $ambiguousNodeAggregatesStatement = <<<SQL
            SELECT
                n.nodeaggregateid, COUNT(n.relationanchorpoint)
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
            GROUP BY
                n.nodeaggregateid
            HAVING
                COUNT(DISTINCT(n.relationanchorpoint)) > 1
        SQL;

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                try {
                    $ambiguousNodeAggregateRecords = $this->dbal->fetchAllAssociative($ambiguousNodeAggregatesStatement, [
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to load ambiguous node aggregates: %s', $e->getMessage()), 1716494110, $e);
                }
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

    public function allNodesHaveAtMostOneParentPerSubgraph(): Result
    {
        $result = new Result();
        $nodeRecordsWithMultipleParentsStatement = <<<SQL
            SELECT
                c.nodeaggregateid
            FROM
                {$this->tableNames->node()} c
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = c.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
            GROUP BY
                c.relationanchorpoint
            HAVING
                COUNT(DISTINCT(h.parentnodeanchor)) > 1
        SQL;

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                try {
                    $nodeRecordsWithMultipleParents = $this->dbal->fetchAllAssociative($nodeRecordsWithMultipleParentsStatement, [
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to load nodes with multiple parents: %s', $e->getMessage()), 1716494223, $e);
                }

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

    public function nodeAggregatesAreConsistentlyTypedPerContentStream(): Result
    {
        $result = new Result();
        $nodeAggregatesStatement = <<<SQL
            SELECT
                DISTINCT n.nodetypename
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
                AND n.nodeaggregateid = :nodeAggregateId
        SQL;
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach (
                $this->findProjectedNodeAggregateIdsInContentStream(
                    $contentStreamId
                ) as $nodeAggregateId
            ) {
                try {
                    $nodeTypeNames = $this->dbal->fetchFirstColumn($nodeAggregatesStatement, [
                        'contentStreamId' => $contentStreamId->value,
                        'nodeAggregateId' => $nodeAggregateId->value
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to load node type names: %s', $e->getMessage()), 1716494446, $e);
                }

                if (count($nodeTypeNames) > 1) {
                    $result->addError(new Error(
                        'Node aggregate ' . $nodeAggregateId->value
                        . ' in content stream ' . $contentStreamId->value
                        . ' is of ambiguous type ("' . implode('","', $nodeTypeNames) . '")',
                        self::ERROR_CODE_NODE_AGGREGATE_IS_AMBIGUOUSLY_TYPED
                    ));
                }
            }
        }

        return $result;
    }

    public function nodeAggregatesAreConsistentlyClassifiedPerContentStream(): Result
    {
        $result = new Result();
        $nodeAggregatesStatement = <<<SQL
            SELECT
                DISTINCT n.classification
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
                AND n.nodeaggregateid = :nodeAggregateId
        SQL;
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach (
                $this->findProjectedNodeAggregateIdsInContentStream(
                    $contentStreamId
                ) as $nodeAggregateId
            ) {
                try {
                    $classifications = $this->dbal->fetchFirstColumn($nodeAggregatesStatement, [
                        'contentStreamId' => $contentStreamId->value,
                        'nodeAggregateId' => $nodeAggregateId->value
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to load node classifications: %s', $e->getMessage()), 1716494466, $e);
                }

                if (count($classifications) > 1) {
                    $result->addError(new Error(
                        'Node aggregate ' . $nodeAggregateId->value
                        . ' in content stream ' . $contentStreamId->value
                        . ' is ambiguously classified ("' . implode('","', $classifications) . '")',
                        self::ERROR_CODE_NODE_AGGREGATE_IS_AMBIGUOUSLY_CLASSIFIED
                    ));
                }
            }
        }

        return $result;
    }

    public function childNodeCoverageIsASubsetOfParentNodeCoverage(): Result
    {
        $result = new Result();
        $excessivelyCoveringStatement = <<<SQL
            SELECT
                n.nodeaggregateid, c.dimensionspacepointhash
            FROM
                {$this->tableNames->hierarchyRelation()} c
                INNER JOIN {$this->tableNames->node()} n ON c.childnodeanchor = n.relationanchorpoint
                LEFT JOIN {$this->tableNames->hierarchyRelation()} p ON c.parentnodeanchor = p.childnodeanchor
            WHERE
                c.contentstreamid = :contentStreamId
                AND p.contentstreamid = :contentStreamId
                AND c.dimensionspacepointhash = p.dimensionspacepointhash
                AND p.childnodeanchor IS NULL
        SQL;
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            try {
                $excessivelyCoveringNodeRecords = $this->dbal->fetchAllAssociative($excessivelyCoveringStatement, [
                    'contentStreamId' => $contentStreamId->value
                ]);
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to load excessively covering nodes: %s', $e->getMessage()), 1716494618, $e);
            }
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

    public function allNodesCoverTheirOrigin(): Result
    {
        $result = new Result();
        $nodesWithMissingOriginCoverageStatement = <<<SQL
            SELECT
                nodeaggregateid, origindimensionspacepointhash
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
                AND nodeaggregateid NOT IN (
                    -- this query finds all nodes whose origin *IS COVERED* by an incoming hierarchy relation.
                    SELECT
                        n.nodeaggregateid
                    FROM
                        {$this->tableNames->node()} n
                        LEFT JOIN {$this->tableNames->hierarchyRelation()} p ON
                            p.childnodeanchor = n.relationanchorpoint
                            AND p.dimensionspacepointhash = n.origindimensionspacepointhash
                        WHERE
                            p.contentstreamid = :contentStreamId
                    )
                    AND classification != :rootClassification
        SQL;
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            try {
                $nodeRecordsWithMissingOriginCoverage = $this->dbal->fetchAllAssociative($nodesWithMissingOriginCoverageStatement, [
                    'contentStreamId' => $contentStreamId->value,
                    'rootClassification' => NodeAggregateClassification::CLASSIFICATION_ROOT->value
                ]);
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to load nodes with missing origin coverage: %s', $e->getMessage()), 1716494752, $e);
            }

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
    private function findProjectedContentStreamIds(): iterable
    {
        $contentStreamIdsStatement = <<<SQL
            SELECT DISTINCT contentstreamid FROM {$this->tableNames->hierarchyRelation()}
        SQL;
        try {
            $contentStreamIds = $this->dbal->fetchFirstColumn($contentStreamIdsStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load content stream ids: %s', $e->getMessage()), 1716494814, $e);
        }
        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    /**
     * Returns all projected dimension space points
     */
    private function findProjectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        $dimensionSpacePointsStatement = <<<SQL
            SELECT dimensionspacepoint FROM {$this->tableNames->dimensionSpacePoints()}
        SQL;
        try {
            $dimensionSpacePoints = $this->dbal->fetchFirstColumn($dimensionSpacePointsStatement);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load dimension space points: %s', $e->getMessage()), 1716494888, $e);
        }
        return new DimensionSpacePointSet(array_map(DimensionSpacePoint::fromJsonString(...), $dimensionSpacePoints));
    }

    /**
     * @return array<NodeAggregateId>
     */
    protected function findProjectedNodeAggregateIdsInContentStream(
        ContentStreamId $contentStreamId
    ): array {
        $nodeAggregateIdsStatement = <<<SQL
            SELECT
                DISTINCT n.nodeaggregateid
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                h.contentstreamid = :contentStreamId
        SQL;
        try {
            $nodeAggregateIds = $this->dbal->fetchFirstColumn($nodeAggregateIdsStatement, [
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load node aggregate ids for content stream: %s', $e->getMessage()), 1716495988, $e);
        }
        return array_map(NodeAggregateId::fromString(...), $nodeAggregateIds);
    }
}
