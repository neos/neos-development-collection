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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Projection\ContentGraph\ProjectionIntegrityViolationDetectorInterface;

/**
 * The Doctrine database backend implementation for projection invariant checks
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
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()
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
            'SELECT * FROM ' . $this->tableNamePrefix . '_hierarchyrelation
                WHERE dimensionspacepointhash != MD5(dimensionspacepoint)'
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
                GROUP BY p.nodeaggregateidentifier, c.nodeaggregateidentifier,
                         h.dimensionspacepointhash, h.contentstreamidentifier
                HAVING uniquenessCounter > 1
                ',
            [
                'rootNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()
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
                    GROUP BY position, parentnodeanchor, contentstreamidentifier, dimensionspacepointhash
                    HAVING COUNT(position) > 1'
        );

        foreach ($ambiguouslySortedHierarchyRelationRecords as $hierarchyRelationRecord) {
            $ambiguouslySortedNodeRecords = $this->client->getConnection()->executeQuery(
                'SELECT nodeaggregateidentifier
                    FROM ' . $this->tableNamePrefix . '_node
                    WHERE relationanchorpoint = :relationAnchorPoint',
                [
                    'relationAnchorPoint' => $hierarchyRelationRecord['childnodeanchor']
                ]
            )->fetchAllAssociative();

            $result->addError(new Error(
                'Siblings ' . implode(', ', array_map(function (array $record) {
                    return $record['nodeaggregateidentifier'];
                }, $ambiguouslySortedNodeRecords))
                . ' are ambiguously sorted in content stream ' . $hierarchyRelationRecord['contentstreamidentifier']
                . ' and dimension space point ' . $hierarchyRelationRecord['dimensionspacepoint'],
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
            'SELECT n.nodeaggregateidentifier, h.contentstreamidentifier
                    FROM ' . $this->tableNamePrefix . '_node n
                INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                    ON h.childnodeanchor = n.relationanchorpoint
                WHERE n.classification = :tethered
              AND h.name IS NULL
              GROUP BY n.nodeaggregateidentifier, h.contentstreamidentifier',
            [
                'tethered' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
            ]
        )->fetchAllAssociative();

        foreach ($unnamedTetheredNodeRecords as $unnamedTetheredNodeRecord) {
            $result->addError(new Error(
                'Node aggregate ' . $unnamedTetheredNodeRecord['nodeaggregateidentifier']
                . ' is unnamed in content stream ' . $unnamedTetheredNodeRecord['contentstreamidentifier'] . '.',
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
            'SELECT c.nodeaggregateidentifier, h.contentstreamidentifier, h.dimensionspacepoint
            FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
            INNER JOIN ' . $this->tableNamePrefix . '_node p
                ON p.relationanchorpoint = h.parentnodeanchor
            INNER JOIN ' . $this->tableNamePrefix . '_restrictionrelation pr
                ON pr.affectednodeaggregateidentifier = p.nodeaggregateidentifier
                AND pr.contentstreamidentifier = h.contentstreamidentifier
                AND pr.dimensionspacepointhash = h.dimensionspacepointhash
            INNER JOIN ' . $this->tableNamePrefix . '_node c
                ON c.relationanchorpoint = h.childnodeanchor
            LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation cr
                ON cr.affectednodeaggregateidentifier = c.nodeaggregateidentifier
                AND cr.contentstreamidentifier = h.contentstreamidentifier
                AND cr.dimensionspacepointhash = h.dimensionspacepointhash
            WHERE cr.affectednodeaggregateidentifier IS NULL'
        )->fetchAllAssociative();

        foreach ($nodeRecordsWithMissingRestrictions as $nodeRecord) {
            $result->addError(new Error(
                'Node aggregate ' . $nodeRecord['nodeaggregateidentifier']
                . ' misses a restriction relation in content stream ' . $nodeRecord['contentstreamidentifier']
                . ' and dimension space point ' . $nodeRecord['dimensionspacepoint'],
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
                ) ON p.nodeaggregateidentifier = r.originnodeaggregateidentifier
                AND ph.contentstreamidentifier = r.contentstreamidentifier
                AND ph.dimensionspacepointhash = r.dimensionspacepointhash
                LEFT JOIN (
                    ' . $this->tableNamePrefix . '_node c
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                        ON c.relationanchorpoint = ch.childnodeanchor
                ) ON c.nodeaggregateidentifier = r.affectednodeaggregateidentifier
                AND ch.contentstreamidentifier = r.contentstreamidentifier
                AND ch.dimensionspacepointhash = r.dimensionspacepointhash
            WHERE p.nodeaggregateidentifier IS NULL
            OR c.nodeaggregateidentifier IS NULL'
        )->fetchAllAssociative();

        foreach ($restrictionRelationRecordsWithoutOriginOrAffectedNode as $relationRecord) {
            $result->addError(new Error(
                'Restriction relation ' . $relationRecord['originnodeaggregateidentifier']
                . ' -> ' . $relationRecord['affectednodeaggregateidentifier']
                . ' does not connect two nodes in content stream ' . $relationRecord['contentstreamidentifier']
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
            'SELECT sh.contentstreamidentifier AS contentstreamIdentifier,
                    s.nodeaggregateidentifier AS sourceNodeAggregateIdentifier,
                    r.destinationnodeaggregateidentifier AS destinationNodeAggregateIdentifier
                FROM ' . $this->tableNamePrefix . '_referencerelation r
                INNER JOIN ' . $this->tableNamePrefix . '_node s ON r.nodeanchorpoint = s.relationanchorpoint
                INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation sh
                    ON r.nodeanchorpoint = sh.childnodeanchor
                LEFT JOIN (
                    ' . $this->tableNamePrefix . '_node d
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation dh
                        ON d.relationanchorpoint = dh.childnodeanchor
                ) ON r.destinationnodeaggregateidentifier = d.nodeaggregateidentifier
                    AND sh.contentstreamidentifier = dh.contentstreamidentifier
                    AND sh.dimensionspacepointhash = dh.dimensionspacepointhash
                WHERE d.nodeaggregateidentifier IS NULL
                GROUP BY s.nodeaggregateidentifier'
        )->fetchAllAssociative();

        foreach ($referenceRelationRecordsWithInvalidTarget as $record) {
            $result->addError(new Error(
                'Destination node aggregate ' . $record['destinationNodeAggregateIdentifier']
                . ' does not cover any dimension space points the source ' . $record['sourceNodeAggregateIdentifier']
                . ' does in content stream ' . $record['contentstreamIdentifier'],
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

        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $nodeAggregateIdentifiersInCycles = $this->client->getConnection()->executeQuery(
                    'WITH RECURSIVE subgraph AS (
    SELECT
     	h.childnodeanchor
    FROM
        ' . $this->tableNamePrefix . '_hierarchyrelation h
    WHERE
        h.parentnodeanchor = :rootAnchorPoint
        AND h.contentstreamidentifier = :contentStreamIdentifier
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
	 	h.contentstreamidentifier = :contentStreamIdentifier
		AND h.dimensionspacepointhash = :dimensionSpacePointHash
)
SELECT nodeaggregateidentifier FROM ' . $this->tableNamePrefix . '_node n
INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
    ON h.childnodeanchor = n.relationanchorpoint
WHERE
    h.contentstreamidentifier = :contentStreamIdentifier
	AND h.dimensionspacepointhash = :dimensionSpacePointHash
    AND relationanchorpoint NOT IN (SELECT * FROM subgraph)',
                    [
                        'rootAnchorPoint' => NodeRelationAnchorPoint::forRootEdge(),
                        'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAllAssociative();

                if (!empty($nodeAggregateIdentifiersInCycles)) {
                    $nodeAggregateIdentifiersInCycles = array_map(function (array $record) {
                        return $record['nodeaggregateidentifier'];
                    }, $nodeAggregateIdentifiersInCycles);

                    $result->addError(new Error(
                        'Subgraph defined by content strean ' . $contentStreamIdentifier
                        . ' and dimension space point ' . (string) $dimensionSpacePoint
                        . ' is cyclic for node aggregates '
                        . implode(',', $nodeAggregateIdentifiersInCycles),
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
    public function nodeAggregateIdentifiersAreUniquePerSubgraph(): Result
    {
        $result = new Result();
        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $ambiguousNodeAggregateRecords = $this->client->getConnection()->executeQuery(
                    'SELECT n.nodeaggregateidentifier, COUNT(n.relationanchorpoint)
                    FROM ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    GROUP BY n.nodeaggregateidentifier
                    HAVING COUNT(DISTINCT(n.relationanchorpoint)) > 1',
                    [
                        'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAllAssociative();

                foreach ($ambiguousNodeAggregateRecords as $ambiguousRecord) {
                    $result->addError(new Error(
                        'Node aggregate ' . $ambiguousRecord['nodeaggregateidentifier']
                        . ' is ambiguous in content stream ' . $contentStreamIdentifier
                        . ' and dimension space point ' . (string) $dimensionSpacePoint,
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
        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            foreach ($this->findProjectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $nodeRecordsWithMultipleParents = $this->client->getConnection()->executeQuery(
                    'SELECT c.nodeaggregateidentifier
                    FROM ' . $this->tableNamePrefix . '_node c
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = c.relationanchorpoint
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    GROUP BY c.relationanchorpoint
                    HAVING COUNT(DISTINCT(h.parentnodeanchor)) > 1',
                    [
                        'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAllAssociative();

                foreach ($nodeRecordsWithMultipleParents as $record) {
                    $result->addError(new Error(
                        'Node aggregate ' . $record['nodeaggregateidentifier']
                        . ' has multiple parents in content stream ' . $contentStreamIdentifier
                        . ' and dimension space point ' . (string) $dimensionSpacePoint,
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
        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            foreach (
                $this->findProjectedNodeAggregateIdentifiersInContentStream(
                    $contentStreamIdentifier
                ) as $nodeAggregateIdentifier
            ) {
                $nodeAggregateRecords = $this->client->getConnection()->executeQuery(
                    'SELECT DISTINCT n.nodetypename FROM ' . $this->tableNamePrefix . '_node n
                        INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                        WHERE h.contentstreamidentifier = :contentStreamIdentifier
                        AND n.nodeaggregateidentifier = :nodeAggregateIdentifier',
                    [
                        'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                        'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
                    ]
                )->fetchAllAssociative();

                if (count($nodeAggregateRecords) > 1) {
                    $result->addError(new Error(
                        'Node aggregate ' . $nodeAggregateIdentifier
                        . ' in content stream ' . $contentStreamIdentifier
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
        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            foreach (
                $this->findProjectedNodeAggregateIdentifiersInContentStream(
                    $contentStreamIdentifier
                ) as $nodeAggregateIdentifier
            ) {
                $nodeAggregateRecords = $this->client->getConnection()->executeQuery(
                    'SELECT DISTINCT n.classification FROM ' . $this->tableNamePrefix . '_node n
                        INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                        WHERE h.contentstreamidentifier = :contentStreamIdentifier
                        AND n.nodeaggregateidentifier = :nodeAggregateIdentifier',
                    [
                        'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                        'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
                    ]
                )->fetchAllAssociative();

                if (count($nodeAggregateRecords) > 1) {
                    $result->addError(new Error(
                        'Node aggregate ' . $nodeAggregateIdentifier
                        . ' in content stream ' . $contentStreamIdentifier
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
        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            $excessivelyCoveringNodeRecords = $this->client->getConnection()->executeQuery(
                'SELECT n.nodeaggregateidentifier, c.dimensionspacepoint
                    FROM ' . $this->tableNamePrefix . '_hierarchyrelation c
                    INNER JOIN ' . $this->tableNamePrefix . '_node n
                        ON c.childnodeanchor = n.relationanchorpoint
                    LEFT JOIN ' . $this->tableNamePrefix . '_hierarchyrelation p
                        ON c.parentnodeanchor = p.childnodeanchor
                    WHERE c.contentstreamidentifier = :contentStreamIdentifier
                    AND p.contentstreamidentifier = :contentStreamIdentifier
                    AND c.dimensionspacepointhash = p.dimensionspacepointhash
                    AND p.childnodeanchor IS NULL',
                [
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier
                ]
            )->fetchAllAssociative();

            foreach ($excessivelyCoveringNodeRecords as $excessivelyCoveringNodeRecord) {
                $result->addError(new Error(
                    'Node aggregate ' . $excessivelyCoveringNodeRecord['nodeaggregateidentifier']
                    . ' in content stream ' . $contentStreamIdentifier
                    . ' covers dimension space point ' . $excessivelyCoveringNodeRecord['dimensionspacepoint']
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
        foreach ($this->findProjectedContentStreamIdentifiers() as $contentStreamIdentifier) {
            $nodeRecordsWithMissingOriginCoverage = $this->client->getConnection()->executeQuery(
                'SELECT nodeaggregateidentifier, origindimensionspacepoint
                    FROM ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                    WHERE
                        h.contentstreamidentifier = :contentStreamIdentifier
                    AND nodeaggregateidentifier NOT IN (
                        -- this query finds all nodes whose origin *IS COVERED* by an incoming hierarchy relation.
                        SELECT n.nodeaggregateidentifier
                        FROM ' . $this->tableNamePrefix . '_node n
                        LEFT JOIN ' . $this->tableNamePrefix . '_hierarchyrelation p
                            ON p.childnodeanchor = n.relationanchorpoint
                            AND p.dimensionspacepointhash = n.origindimensionspacepointhash
                            WHERE p.contentstreamidentifier = :contentStreamIdentifier
                    )
                    AND classification != :rootClassification',
                [
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'rootClassification' => NodeAggregateClassification::CLASSIFICATION_ROOT->value
                ]
            )->fetchAllAssociative();

            foreach ($nodeRecordsWithMissingOriginCoverage as $nodeRecord) {
                $result->addError(new Error(
                    'Node aggregate ' . $nodeRecord['nodeaggregateidentifier']
                    . ' in content stream ' . $contentStreamIdentifier
                    . ' does not cover its origin dimension space point ' . $nodeRecord['origindimensionspacepoint']
                    . '.',
                    self::ERROR_CODE_NODE_DOES_NOT_COVER_ITS_ORIGIN
                ));
            }
        }

        return $result;
    }

    /**
     * Returns all content stream identifiers
     *
     * @return iterable<ContentStreamIdentifier>
     */
    protected function findProjectedContentStreamIdentifiers(): iterable
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery(
            'SELECT DISTINCT contentstreamidentifier FROM ' . $this->tableNamePrefix . '_hierarchyrelation'
        )->fetchAllAssociative();

        return array_map(function (array $row) {
            return ContentStreamIdentifier::fromString($row['contentstreamidentifier']);
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
            'SELECT DISTINCT dimensionspacepoint FROM ' . $this->tableNamePrefix . '_hierarchyrelation'
        )->fetchAllAssociative();

        $records = array_map(function (array $record) {
            return DimensionSpacePoint::fromJsonString($record['dimensionspacepoint']);
        }, $records);

        return new DimensionSpacePointSet($records);
    }

    /**
     * @return array<int,NodeAggregateIdentifier>
     * @throws \Doctrine\DBAL\Exception | \Doctrine\DBAL\Driver\Exception
     */
    protected function findProjectedNodeAggregateIdentifiersInContentStream(
        ContentStreamIdentifier $contentStreamIdentifier
    ): array {
        $records = $this->client->getConnection()->executeQuery(
            'SELECT DISTINCT nodeaggregateidentifier FROM ' . $this->tableNamePrefix . '_node'
        )->fetchAllAssociative();

        return array_map(function (array $record) {
            return NodeAggregateIdentifier::fromString($record['nodeaggregateidentifier']);
        }, $records);
    }
}
