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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectorInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;

/**
 * The PostgreSQL database backend implementation for projection invariant checks
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

        // Check that parent nodes exist for all hierarchy relations (except root edges)
        $disconnectedParentStatement = <<<SQL
            SELECT h.* FROM {$this->tableNames->hierarchyRelation()} h
            LEFT JOIN {$this->tableNames->node()} p ON h.parentnodeanchor = p.relationanchorpoint
            WHERE h.parentnodeanchor != :rootNodeAnchor
            AND p.relationanchorpoint IS NULL
        SQL;
        try {
            $disconnectedParentRecords = $this->dbal->executeQuery($disconnectedParentStatement, [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
            ])->fetchAllAssociative();
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load hierarchy relations with missing parent: %s', $e->getMessage()), 1716491735, $e);
        }

        foreach ($disconnectedParentRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' has a missing parent node.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        // Check that all child node anchors in the arrays actually exist
        $disconnectedChildStatement = <<<SQL
            SELECT h.contentstreamid, h.parentnodeanchor, h.dimensionspacepointhash, child_anchor
            FROM {$this->tableNames->hierarchyRelation()} h
            CROSS JOIN unnest(h.childnodeanchors) AS child_anchor
            LEFT JOIN {$this->tableNames->node()} c ON child_anchor = c.relationanchorpoint
            WHERE c.relationanchorpoint IS NULL
        SQL;
        try {
            $disconnectedChildRecords = $this->dbal->fetchAllAssociative($disconnectedChildStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load hierarchy relations with missing child: %s', $e->getMessage()), 1716491736, $e);
        }

        foreach ($disconnectedChildRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation (content stream ' . $record['contentstreamid']
                . ', parent anchor ' . $record['parentnodeanchor']
                . ', DSP hash ' . $record['dimensionspacepointhash']
                . ') references non-existent child anchor ' . $record['child_anchor'] . '.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        // Check that dimension space point hashes are valid
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
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load invalid hashed hierarchy relations: %s', $e->getMessage()), 1716491994, $e);
        }

        foreach ($invalidlyHashedHierarchyRelationRecords as $record) {
            $result->addError(new Error(
                'Hierarchy relation ' . \json_encode($record)
                . ' has an invalid dimension space point hash.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        // Check that a child node aggregate appears at most once per subgraph
        // (i.e. not in multiple parents' childnodeanchors arrays)
        $childAppearingMultipleTimesStatement = <<<SQL
            SELECT
                c.nodeaggregateid,
                h.dimensionspacepointhash,
                h.contentstreamid,
                COUNT(*) as occurrence_count
            FROM {$this->tableNames->hierarchyRelation()} h
            CROSS JOIN unnest(h.childnodeanchors) AS child_anchor
            INNER JOIN {$this->tableNames->node()} c ON child_anchor = c.relationanchorpoint
            WHERE h.parentnodeanchor != :rootNodeAnchor
            GROUP BY
                c.nodeaggregateid,
                h.dimensionspacepointhash,
                h.contentstreamid
            HAVING COUNT(*) > 1
        SQL;
        try {
            $childAppearingMultipleTimesRecords = $this->dbal->fetchAllAssociative($childAppearingMultipleTimesStatement, [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load hierarchy relations that appear multiple times: %s', $e->getMessage()), 1716495277, $e);
        }
        foreach ($childAppearingMultipleTimesRecords as $record) {
            $result->addError(new Error(
                'Node aggregate ' . $record['nodeaggregateid']
                . ' appears ' . $record['occurrence_count'] . ' times in content stream ' . $record['contentstreamid']
                . ' and dimension space point hash ' . $record['dimensionspacepointhash'] . '.',
                self::ERROR_CODE_HIERARCHY_INTEGRITY_IS_COMPROMISED
            ));
        }

        return $result;
    }

    public function siblingsAreDistinctlySorted(): Result
    {
        $result = new Result();

        // In the hypergraph model, sorting is determined by array position.
        // Check for duplicate anchors in the childnodeanchors array.
        $duplicateChildAnchorsStatement = <<<SQL
            SELECT
                h.contentstreamid,
                h.dimensionspacepointhash,
                h.parentnodeanchor,
                child_anchor,
                COUNT(*) as duplicate_count
            FROM {$this->tableNames->hierarchyRelation()} h
            CROSS JOIN unnest(h.childnodeanchors) AS child_anchor
            GROUP BY
                h.contentstreamid,
                h.dimensionspacepointhash,
                h.parentnodeanchor,
                child_anchor
            HAVING COUNT(*) > 1
        SQL;
        try {
            $duplicateChildAnchorRecords = $this->dbal->fetchAllAssociative($duplicateChildAnchorsStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load ambiguously sorted hierarchy relations: %s', $e->getMessage()), 1716492251, $e);
        }

        $dimensionSpacePoints = $this->findProjectedDimensionSpacePoints();

        foreach ($duplicateChildAnchorRecords as $record) {
            $result->addError(new Error(
                'Child anchor ' . $record['child_anchor']
                . ' appears ' . $record['duplicate_count'] . ' times in the same hierarchy relation'
                . ' in content stream ' . $record['contentstreamid']
                . ' and dimension space point ' . $dimensionSpacePoints[$record['dimensionspacepointhash']]?->toJson(),
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
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE
                n.classification = :tethered
                AND (n.nodename IS NULL OR n.nodename = '')
            GROUP BY
                n.nodeaggregateid, h.contentstreamid
        SQL;
        try {
            $unnamedTetheredNodeRecords = $this->dbal->fetchAllAssociative($unnamedTetheredNodesStatement, [
                'tethered' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
            ]);
        } catch (DBALException $e) {
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

        // In the hypergraph model, subtree tags are stored as:
        // {"<childAnchor>": {"<tagName>": true/null}, ...}
        // We need to check that if a parent node has tags, all its children inherit those tags.
        //
        // For each child node, get the tags from its parent hierarchy relation entry.
        // Then check that the child's own tags (in the hierarchy relation where it is a child)
        // contain all the tags that the parent has.
        $hierarchyRelationsWithMissingSubtreeTagsStatement = <<<SQL
            WITH parent_child_tags AS (
                -- For each child node, find its parent's tags and the child's own tags
                SELECT
                    ph.contentstreamid,
                    ph.dimensionspacepointhash,
                    ph.parentnodeanchor,
                    parent_anchor AS child_anchor,
                    COALESCE(ph.subtreetags->(parent_anchor::text), '{}') AS parent_tags,
                    COALESCE(ch.subtreetags->(parent_anchor::text), '{}') AS child_tags
                FROM {$this->tableNames->hierarchyRelation()} ph
                CROSS JOIN unnest(ph.childnodeanchors) AS parent_anchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON ch.parentnodeanchor = parent_anchor
                    AND ch.contentstreamid = ph.contentstreamid
                    AND ch.dimensionspacepointhash = ph.dimensionspacepointhash
                CROSS JOIN unnest(ch.childnodeanchors) AS child_in_ch
                WHERE ph.subtreetags IS NOT NULL
                    AND ph.subtreetags->(parent_anchor::text) IS NOT NULL
                    AND ph.subtreetags->(parent_anchor::text) != '{}'::jsonb
            )
            SELECT DISTINCT
                pct.contentstreamid,
                pct.dimensionspacepointhash,
                pct.parentnodeanchor,
                pct.child_anchor,
                pct.parent_tags,
                pct.child_tags
            FROM parent_child_tags pct
            WHERE EXISTS (
                SELECT tag_key
                FROM jsonb_object_keys(pct.parent_tags) AS tag_key
                WHERE NOT jsonb_exists(pct.child_tags, tag_key)
            )
        SQL;
        try {
            $hierarchyRelationsWithMissingSubtreeTags = $this->dbal->fetchAllAssociative($hierarchyRelationsWithMissingSubtreeTagsStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load hierarchy relations with missing subtree tags: %s', $e->getMessage()), 1716492658, $e);
        }

        foreach ($hierarchyRelationsWithMissingSubtreeTags as $hierarchyRelation) {
            $result->addError(new Error(
                'Hierarchy relation (content stream ' . $hierarchyRelation['contentstreamid']
                . ', parent anchor ' . $hierarchyRelation['parentnodeanchor']
                . ', DSP hash ' . $hierarchyRelation['dimensionspacepointhash']
                . ') child anchor ' . $hierarchyRelation['child_anchor']
                . ' is missing inherited subtree tags from the parent. Parent tags: ' . $hierarchyRelation['parent_tags']
                . ', child tags: ' . $hierarchyRelation['child_tags'],
                self::ERROR_CODE_NODE_HAS_MISSING_SUBTREE_TAG
            ));
        }

        return $result;
    }

    public function referenceIntegrityIsProvided(): Result
    {
        $result = new Result();

        // Check references detached from source
        $referenceRelationRecordsDetachedFromSourceStatement = <<<SQL
            SELECT
                *
            FROM
                {$this->tableNames->referenceRelation()}
            WHERE
                sourcenodeanchor NOT IN (
                    SELECT relationanchorpoint FROM {$this->tableNames->node()}
                )
        SQL;
        try {
            $referenceRelationRecordsDetachedFromSource = $this->dbal->fetchAllAssociative($referenceRelationRecordsDetachedFromSourceStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load detached reference relations: %s', $e->getMessage()), 1716492786, $e);
        }

        foreach ($referenceRelationRecordsDetachedFromSource as $record) {
            $result->addError(new Error(
                'Reference relation ' . \json_encode($record)
                . ' is detached from its origin.',
                self::ERROR_CODE_REFERENCE_INTEGRITY_IS_COMPROMISED
            ));
        }

        // Check references with invalid target
        $referenceRelationRecordsWithInvalidTargetStatement = <<<SQL
            SELECT
                sh.contentstreamid AS "contentstreamId",
                s.nodeaggregateid AS "sourceNodeAggregateId",
                r.targetnodeaggregateid AS "destinationNodeAggregateId"
            FROM
                {$this->tableNames->referenceRelation()} r
                INNER JOIN {$this->tableNames->node()} s ON r.sourcenodeanchor = s.relationanchorpoint
                INNER JOIN {$this->tableNames->hierarchyRelation()} sh
                    ON r.sourcenodeanchor = ANY(sh.childnodeanchors)
                LEFT JOIN (
                    {$this->tableNames->node()} d
                    INNER JOIN {$this->tableNames->hierarchyRelation()} dh
                        ON d.relationanchorpoint = ANY(dh.childnodeanchors)
                ) ON r.targetnodeaggregateid = d.nodeaggregateid
                  AND sh.contentstreamid = dh.contentstreamid
                  AND sh.dimensionspacepointhash = dh.dimensionspacepointhash
                WHERE
                    d.nodeaggregateid IS NULL
                GROUP BY
                    s.nodeaggregateid,
                    sh.contentstreamid,
                    r.targetnodeaggregateid
        SQL;
        try {
            $referenceRelationRecordsWithInvalidTarget = $this->dbal->fetchAllAssociative($referenceRelationRecordsWithInvalidTargetStatement);
        } catch (DBALException $e) {
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
                    child_anchor
                FROM
                    {$this->tableNames->hierarchyRelation()} h
                CROSS JOIN unnest(h.childnodeanchors) AS child_anchor
                WHERE
                    h.parentnodeanchor = :rootAnchorPoint
                    AND h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                UNION
                 -- --------------------------------
                 -- RECURSIVE query: do one "child" query step
                 -- --------------------------------
                 SELECT
                    gc_anchor
                 FROM
                    subgraph p
                 INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON h.parentnodeanchor = p.child_anchor
                 CROSS JOIN unnest(h.childnodeanchors) AS gc_anchor
                 WHERE
                    h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
            )
            SELECT nodeaggregateid FROM {$this->tableNames->node()} n
            INNER JOIN {$this->tableNames->hierarchyRelation()} h
                ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE
                h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND n.relationanchorpoint NOT IN (SELECT child_anchor FROM subgraph)
        SQL;

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                try {
                    $nodeAggregateIdsInCycles = $this->dbal->fetchFirstColumn($nodeAggregateIdsInCyclesStatement, [
                        'rootAnchorPoint' => NodeRelationAnchorPoint::forRootEdge()->value,
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DBALException $e) {
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
                n.nodeaggregateid, COUNT(DISTINCT n.relationanchorpoint)
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE
                h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
            GROUP BY
                n.nodeaggregateid
            HAVING
                COUNT(DISTINCT n.relationanchorpoint) > 1
        SQL;

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                try {
                    $ambiguousNodeAggregateRecords = $this->dbal->fetchAllAssociative($ambiguousNodeAggregatesStatement, [
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DBALException $e) {
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
        // In the hypergraph model, a child anchor appearing in multiple hierarchy relations
        // (with different parents) for the same subgraph means multiple parents
        $nodeRecordsWithMultipleParentsStatement = <<<SQL
            SELECT
                n.nodeaggregateid
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE
                h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
            GROUP BY
                n.relationanchorpoint, n.nodeaggregateid
            HAVING
                COUNT(DISTINCT h.parentnodeanchor) > 1
        SQL;

        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                try {
                    $nodeRecordsWithMultipleParents = $this->dbal->fetchAllAssociative($nodeRecordsWithMultipleParentsStatement, [
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DBALException $e) {
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
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
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
                } catch (DBALException $e) {
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
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
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
                } catch (DBALException $e) {
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
        // Find child nodes that are covered by a dimension space point
        // where their parent is not covered
        $excessivelyCoveringStatement = <<<SQL
            SELECT
                n.nodeaggregateid, ch.dimensionspacepointhash
            FROM
                {$this->tableNames->hierarchyRelation()} ch
                CROSS JOIN unnest(ch.childnodeanchors) AS child_anchor
                INNER JOIN {$this->tableNames->node()} n ON child_anchor = n.relationanchorpoint
                LEFT JOIN {$this->tableNames->hierarchyRelation()} ph
                    ON ch.parentnodeanchor = ANY(ph.childnodeanchors)
                    AND ph.contentstreamid = ch.contentstreamid
                    AND ph.dimensionspacepointhash = ch.dimensionspacepointhash
            WHERE
                ch.contentstreamid = :contentStreamId
                AND ch.parentnodeanchor != :rootNodeAnchor
                AND ph.childnodeanchors IS NULL
        SQL;
        foreach ($this->findProjectedContentStreamIds() as $contentStreamId) {
            try {
                $excessivelyCoveringNodeRecords = $this->dbal->fetchAllAssociative($excessivelyCoveringStatement, [
                    'contentStreamId' => $contentStreamId->value,
                    'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value
                ]);
            } catch (DBALException $e) {
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
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE
                h.contentstreamid = :contentStreamId
                AND nodeaggregateid NOT IN (
                    -- this query finds all nodes whose origin *IS COVERED* by an incoming hierarchy relation.
                    SELECT
                        n2.nodeaggregateid
                    FROM
                        {$this->tableNames->node()} n2
                        INNER JOIN {$this->tableNames->hierarchyRelation()} p ON
                            n2.relationanchorpoint = ANY(p.childnodeanchors)
                            AND p.dimensionspacepointhash = n2.origindimensionspacepointhash
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
            } catch (DBALException $e) {
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
        } catch (DBALException $e) {
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
        } catch (DBALException $e) {
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
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE
                h.contentstreamid = :contentStreamId
        SQL;
        try {
            $nodeAggregateIds = $this->dbal->fetchFirstColumn($nodeAggregateIdsStatement, [
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load node aggregate ids for content stream: %s', $e->getMessage()), 1716495988, $e);
        }
        return array_map(NodeAggregateId::fromString(...), $nodeAggregateIds);
    }
}
