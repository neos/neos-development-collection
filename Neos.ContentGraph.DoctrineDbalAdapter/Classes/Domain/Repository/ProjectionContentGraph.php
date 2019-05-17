<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * The read only content graph for projection support
 *
 * @Flow\Scope("singleton")
 */
class ProjectionContentGraph
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;


    /**
     * @return bool
     * @throws DBALException
     */
    public function isEmpty(): bool
    {
        return (int)$this->getDatabaseConnection()
                ->executeQuery('SELECT count(*) FROM neos_contentgraph_node')
                ->fetch()['count'] > 0
            && (int)$this->getDatabaseConnection()
                ->executeQuery('SELECT count(*) FROM neos_contentgraph_hierarchyrelation')
                ->fetch()['count'] > 0;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @return NodeRecord|null
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNode(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        DimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeRecord {
        $params = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->getHash()
        ];
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT p.*, ph.contentstreamidentifier, ph.name FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
 WHERE c.nodeaggregateidentifier = :childNodeAggregateIdentifier
 AND c.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND ph.contentstreamidentifier = :contentStreamIdentifier
 AND ch.contentstreamidentifier = :contentStreamIdentifier
 AND ph.dimensionspacepointhash = :originDimensionSpacePointHash
 AND ch.dimensionspacepointhash = :originDimensionSpacePointHash',
            $params
        )->fetch();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return NodeRecord|null
     * @throws DBALException
     * @throws \Exception
     */
    public function getNodeInAggregate(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $dimensionSpacePoint): ?NodeRecord
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
            ]
        )->fetch();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @return NodeRecord|null
     * @throws \Exception
     */
    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeRecord {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :originDimensionSpacePointHash',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->getHash()
            ]
        )->fetch();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return NodeRelationAnchorPoint|null
     * @throws DBALException
     */
    public function getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $originDimensionSpacePoint, ContentStreamIdentifier $contentStreamIdentifier): ?NodeRelationAnchorPoint
    {
        $rows = $this->getDatabaseConnection()->executeQuery(
            'SELECT DISTINCT n.relationanchorpoint FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->getHash(),
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            ]
        )->fetchAll();

        if (count($rows) > 1) {
            throw new \Exception('TODO: I believe this shall not happen; but we need to think this through in detail if it does!!!');
        }

        if (count($rows) === 1) {
            return NodeRelationAnchorPoint::fromString($rows[0]['relationanchorpoint']);
        } else {
            return null;
        }
    }


    /**
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return NodeRecord|null
     * @throws DBALException
     */
    public function getNodeByAnchorPoint(NodeRelationAnchorPoint $nodeRelationAnchorPoint): ?NodeRecord
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 WHERE n.relationanchorpoint = :relationAnchorPoint',
            [
                'relationAnchorPoint' => (string)$nodeRelationAnchorPoint,
            ]
        )->fetch();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws DBALException
     */
    public function determineHierarchyRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        if (!$parentAnchorPoint && !$childAnchorPoint) {
            throw new \InvalidArgumentException('You must specify either parent or child node anchor to determine a hierarchy relation position', 1519847447);
        }
        if ($succeedingSiblingAnchorPoint) {
            $succeedingSiblingRelation = $this->getDatabaseConnection()->executeQuery(
                'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.childnodeanchor = :succeedingSiblingAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'succeedingSiblingAnchorPoint' => (string)$succeedingSiblingAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch();

            $succeedingSiblingPosition = (int)$succeedingSiblingRelation['position'];
            $parentAnchorPoint = $succeedingSiblingRelation['parentnodeanchor'];

            $precedingSiblingPosition = $this->getDatabaseConnection()->executeQuery(
                'SELECT MAX(h.position) AS position FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.parentnodeanchor = :anchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          AND h.position < :position',
                [
                    'anchorPoint' => $parentAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    'position' => $succeedingSiblingPosition
                ]
            )->fetch()['position'] ?? null;
            if (!is_null($precedingSiblingPosition)) {
                $precedingSiblingPosition = (int) $precedingSiblingPosition;
            }

            if (is_null($precedingSiblingPosition)) {
                $position = $succeedingSiblingPosition - GraphProjector::RELATION_DEFAULT_OFFSET;
            } else {
                $position = ($succeedingSiblingPosition + $precedingSiblingPosition) / 2;
            }
        } else {
            if (!$parentAnchorPoint) {
                $childHierarchyRelationData = $this->getDatabaseConnection()->executeQuery(
                    'SELECT h.parentnodeanchor FROM neos_contentgraph_hierarchyrelation h
                      WHERE h.childnodeanchor = :childAnchorPoint
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                    [
                        'childAnchorPoint' => $childAnchorPoint,
                        'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                    ]
                )->fetch();
                $parentAnchorPoint = NodeRelationAnchorPoint::fromString($childHierarchyRelationData['parentnodeanchor']);
            }
            $rightmostSucceedingSiblingRelationData = $this->getDatabaseConnection()->executeQuery(
                'SELECT MAX(h.position) AS position FROM neos_contentgraph_hierarchyrelation h
                      WHERE h.parentnodeanchor = :parentAnchorPoint
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'parentAnchorPoint' => $parentAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch();

            if ($rightmostSucceedingSiblingRelationData) {
                $position = ((int)$rightmostSucceedingSiblingRelationData['position']) + GraphProjector::RELATION_DEFAULT_OFFSET;
            } else {
                $position = 0;
            }
        }

        return $position;
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function getOutgoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $relations = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
                      WHERE h.parentnodeanchor = :parentAnchorPoint
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'parentAnchorPoint' => (string)$parentAnchorPoint,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
            ]
        )->fetchAll() as $relationData) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function getIngoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $relations = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
                      WHERE h.childnodeanchor = :childAnchorPoint
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'childAnchorPoint' => (string)$childAnchorPoint,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
            ]
        )->fetchAll() as $relationData) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet|null $restrictToSet
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function findIngoingHierarchyRelationsForNode(NodeRelationAnchorPoint $childAnchorPoint, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePointSet $restrictToSet = null): array
    {
        $relations = [];
        $query = 'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
    WHERE h.childnodeanchor = :childAnchorPoint
    AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'childAnchorPoint' => (string)$childAnchorPoint,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];
        $types = [];

        if ($restrictToSet) {
            $query .= '
    AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters, $types)->fetchAll() as $relationData) {
            $relations[$relationData['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet|null $restrictToSet
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function findOutgoingHierarchyRelationsForNode(NodeRelationAnchorPoint $parentAnchorPoint, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePointSet $restrictToSet = null): array
    {
        $relations = [];
        $query = 'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
    WHERE h.parentnodeanchor = :parentAnchorPoint
    AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'parentAnchorPoint' => (string)$parentAnchorPoint,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];
        $types = [];

        if ($restrictToSet) {
            $query .= '
    AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters, $types)->fetchAll() as $relationData) {
            $relations[$relationData['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @return array|HierarchyRelation[]
     * @throws DBALException
     */
    public function findOutgoingHierarchyRelationsForNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): array {
        $relations = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
 INNER JOIN neos_contentgraph_node n ON h.parentnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHashes' => $dimensionSpacePointSet->getPointHashes()
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAll() as $relationData) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet|null $dimensionSpacePointSet
     * @return array|HierarchyRelation[]
     * @throws DBALException
     */
    public function findIngoingHierarchyRelationsForNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array {
        $relations = [];

        $query = 'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
            INNER JOIN neos_contentgraph_node n ON h.childnodeanchor = n.relationanchorpoint
            WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
            AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
        ];
        $types = [];

        if ($dimensionSpacePointSet !== null) {
            $query .= '
                AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $dimensionSpacePointSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters, $types)->fetchAll() as $relationData) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return array
     * @throws DBALException
     */
    public function getAllContentStreamIdentifiersAnchorPointIsContainedIn(NodeRelationAnchorPoint $nodeRelationAnchorPoint): array
    {
        $contentStreamIdentifiers = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT DISTINCT h.contentstreamidentifier
                      FROM neos_contentgraph_hierarchyrelation h
                      WHERE h.childnodeanchor = :nodeRelationAnchorPoint',
            [
                'nodeRelationAnchorPoint' => (string)$nodeRelationAnchorPoint,
            ]
        )->fetchAll() as $row) {
            $contentStreamIdentifiers[] = ContentStreamIdentifier::fromString($row['contentstreamidentifier']);
        }

        return $contentStreamIdentifiers;
    }

    /**
     * Finds all descendant node aggregate identifiers, indexed by dimension space point hash
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $entryNodeAggregateIdentifier
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints
     * @return array|NodeAggregateIdentifier[][]
     * @throws DBALException
     */
    public function findDescendantNodeAggregateIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $entryNodeAggregateIdentifier,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): array {
        $rows = $this->getDatabaseConnection()->executeQuery('
            -- ProjectionContentGraph::findDescendantNodeAggregateIdentifiers

            WITH RECURSIVE nestedNodes AS (
                    -- --------------------------------
                    -- INITIAL query: select the root nodes
                    -- --------------------------------
                    SELECT
                       n.nodeaggregateidentifier,
                       n.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM
                        neos_contentgraph_node n
                    INNER JOIN neos_contentgraph_hierarchyrelation h
                        on h.childnodeanchor = n.relationanchorpoint
                    WHERE n.nodeaggregateidentifier = :entryNodeAggregateIdentifier
                    AND h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)

                UNION
                    -- --------------------------------
                    -- RECURSIVE query: do one "child" query step
                    -- --------------------------------
                    SELECT
                        c.nodeaggregateidentifier,
                        c.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM
                        nestedNodes p
                    INNER JOIN neos_contentgraph_hierarchyrelation h
                        on h.parentnodeanchor = p.relationanchorpoint
                    INNER JOIN neos_contentgraph_node c
                        on h.childnodeanchor = c.relationanchorpoint
                    WHERE
                        h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
            )
            select nodeaggregateidentifier, dimensionspacepointhash from nestedNodes
            ',
            [
                'entryNodeAggregateIdentifier' => (string)$entryNodeAggregateIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'affectedDimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAll();

        $nodeAggregateIdentifiers = [];
        foreach ($rows as $row) {
            $nodeAggregateIdentifiers[$row['nodeaggregateidentifier']][$row['dimensionspacepointhash']] = NodeAggregateIdentifier::fromString($row['nodeaggregateidentifier']);
        }

        return $nodeAggregateIdentifiers;
    }

    /**
     * @param array $rawData
     * @return HierarchyRelation
     */
    protected function mapRawDataToHierarchyRelation(array $rawData): HierarchyRelation
    {
        $dimensionSpacePointData = json_decode($rawData['dimensionspacepoint'], true);
        $dimensionSpacePoint = new DimensionSpacePoint($dimensionSpacePointData);

        return new HierarchyRelation(
            NodeRelationAnchorPoint::fromString($rawData['parentnodeanchor']),
            NodeRelationAnchorPoint::fromString($rawData['childnodeanchor']),
            $rawData['name'] ? NodeName::fromString($rawData['name']) : null,
            ContentStreamIdentifier::fromString($rawData['contentstreamidentifier']),
            $dimensionSpacePoint,
            $rawData['dimensionspacepointhash'],
            $rawData['position']
        );
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
