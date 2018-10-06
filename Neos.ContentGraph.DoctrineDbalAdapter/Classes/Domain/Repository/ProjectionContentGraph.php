<?php

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
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeAggregate;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
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
     * @throws \Doctrine\DBAL\DBALException
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
     * @param NodeIdentifier $nodeIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNode(NodeIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier): ?Node
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeidentifier = :nodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            ]
        )->fetch();

        if (!$nodeRow) {
            // Check for root node

            $nodeRow = $this->getDatabaseConnection()->executeQuery(
                'SELECT n.* FROM neos_contentgraph_node n
 WHERE n.nodeidentifier = :nodeIdentifier',
                [
                    'nodeIdentifier' => $nodeIdentifier
                ]
            )->fetch();

            // We always allow root nodes
            return $nodeRow && empty($nodeRow['dimensionspacepointhash']) ? Node::fromDatabaseRow($nodeRow) : null;
        }

        return Node::fromDatabaseRow($nodeRow);
    }

    /**
     * @param NodeIdentifier $childNodeIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findParentNode(NodeIdentifier $childNodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        $params = [
            'childNodeIdentifier' => (string)$childNodeIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
        ];
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT p.*, h.contentstreamidentifier, hp.name FROM neos_contentgraph_node p
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 INNER JOIN neos_contentgraph_hierarchyrelation hp ON hp.childnodeanchor = p.relationanchorpoint
 WHERE c.nodeidentifier = :childNodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND hp.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash
 AND hp.dimensionspacepointhash = :dimensionSpacePointHash',
            $params
        )->fetch();

        return $nodeRow ? Node::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNodeInAggregate(NodeAggregateIdentifier $nodeAggregateIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
            ]
        )->fetch();

        return $nodeRow ? Node::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNodeByNodeIdentifierAndContentStream(NodeIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier): ?Node
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeidentifier = :nodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        return $nodeRow ? Node::fromDatabaseRow($nodeRow) : null;
    }


    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return NodeRelationAnchorPoint|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function getAnchorPointForNodeAndContentStream(NodeIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier): ?NodeRelationAnchorPoint
    {
        $rows = $this->getDatabaseConnection()->executeQuery(
            'SELECT DISTINCT n.relationanchorpoint FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeidentifier = :nodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            ]
        )->fetchAll();

        if (count($rows) > 1) {
            throw new \Exception('TODO: I believe this shall not happen; but we need to think this through in detail if it does!!!');
        }

        if (count($rows) === 1) {
            return new NodeRelationAnchorPoint($rows[0]['relationanchorpoint']);
        } else {
            return null;
        }
    }


    /**
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return Node|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNodeByAnchorPoint(NodeRelationAnchorPoint $nodeRelationAnchorPoint): ?Node
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 WHERE n.relationanchorpoint = :relationAnchorPoint',
            [
                'relationAnchorPoint' => (string)$nodeRelationAnchorPoint,
            ]
        )->fetch();

        return $nodeRow ? Node::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function determineHierarchyRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int
    {
        if (!$parentAnchorPoint && !$childAnchorPoint) {
            throw new \InvalidArgumentException('You must specify either parent or child node anchor to determine a hierarchy relation position', 1519847447);
        }
        if ($succeedingSiblingAnchorPoint) {
            $succeedingSiblingPosition = (int)$this->getDatabaseConnection()->executeQuery(
                'SELECT h.position FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.childnodeanchor = :succeedingSiblingAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'succeedingSiblingAnchorPoint' => (string)$succeedingSiblingAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]

            )->fetch()['position'];

            $succeedingSiblingRelation = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.' . ($parentAnchorPoint ? 'parentnodeanchor' : 'childnodeanhor') . ' = :parentOrChildAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          AND h.`position` > :position',
                [
                    'parentOrChildAnchorPoint' => $parentAnchorPoint ?: $childAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    'position' => $succeedingSiblingPosition
                ]
            )->fetch();

            if (!is_null($succeedingSiblingRelation['position'])) {
                $position = ($succeedingSiblingPosition + (int)$succeedingSiblingRelation['position']) / 2;
            } else {
                $position = $succeedingSiblingPosition + GraphProjector::RELATION_DEFAULT_OFFSET;
            }
        } else {
            $rightmostSucceedingSiblingRelation = $this->getDatabaseConnection()->executeQuery(
                'SELECT MAX(h.position) AS `position` FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.' . ($parentAnchorPoint ? 'parentnodeanchor' : 'childnodeanhor') . ' = :parentOrChildAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          ORDER BY h.`position` DESC',
                [
                    'parentOrChildAnchorPoint' => $parentAnchorPoint ?: $childAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch();

            if ($rightmostSucceedingSiblingRelation) {
                $position = ((int)$rightmostSucceedingSiblingRelation['position']) + GraphProjector::RELATION_DEFAULT_OFFSET;
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOutboundHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): array
    {
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getInboundHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): array
    {
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findInboundHierarchyRelationsForNode(NodeRelationAnchorPoint $childAnchorPoint, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePointSet $restrictToSet = null): array
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findOutboundHierarchyRelationsForNode(NodeRelationAnchorPoint $parentAnchorPoint, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePointSet $restrictToSet = null): array
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
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeIdentifier $childNodeIdentifier
     * @return HierarchyRelation|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findHierarchyRelation(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $parentNodeIdentifier, NodeIdentifier $childNodeIdentifier): ?HierarchyRelation
    {
        $relationData = $this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
 INNER JOIN neos_contentgraph_node p ON h.parentnodeanchor = p.relationanchorpoint
 INNER JOIN neos_contentgraph_node c ON h.childnodeanchor = c.relationanchorpoint
 WHERE h.contentstreamidentifier = :contentStreamIdentifier 
 AND p.nodeidentifier = :parentNodeIdentifier
 AND c.nodeidentifier = :childNodeIdentifier',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'parentNodeIdentifier' => (string)$parentNodeIdentifier,
                'childNodeIdentifier' => (string)$childNodeIdentifier
            ]
        )->fetch();

        return $relationData ? $this->mapRawDataToHierarchyRelation($relationData) : null;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @return array|HierarchyRelation[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findOutboundHierarchyRelationsForNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): array
    {
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findInboundHierarchyRelationsForNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array
    {
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
     * @param string $parentNodesIdentifierInGraph
     * @param array $subgraphIdentityHashs
     * @return array|HierarchyRelation[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findOutboundHierarchyRelationsForNodeAndSubgraphs(string $parentNodesIdentifierInGraph, array $subgraphIdentityHashs): array
    {
        // TODO needs to be fixed
        $relations = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
 WHERE parentnodesidentifieringraph = :parentNodesIdentifierInGraph
 AND subgraphIdentityHash IN (:subgraphIdentityHashs)',
            [
                'parentNodesIdentifierInGraph' => $parentNodesIdentifierInGraph,
                'subgraphIdentityHashs' => $subgraphIdentityHashs
            ],
            [
                'subgraphIdentityHashs' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAll() as $relationData) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return array
     * @throws \Doctrine\DBAL\DBALException
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
            $contentStreamIdentifiers[] = new ContentStreamIdentifier($row['contentstreamidentifier']);
        }

        return $contentStreamIdentifiers;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAggregate|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNodeAggregate($contentStreamIdentifier, $nodeAggregateIdentifier): ?NodeAggregate
    {
        $nodeAggregateRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.nodetypename, n.nodeaggregateidentifier FROM neos_contentgraph_node n
                        INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                        WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                        AND h.contentstreamidentifier = :contentStreamIdentifier
                        LIMIT 1',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        return $nodeAggregateRow ? NodeAggregate::fromDatabaseRow($nodeAggregateRow) : null;
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
            new NodeRelationAnchorPoint($rawData['parentnodeanchor']),
            new NodeRelationAnchorPoint($rawData['childnodeanchor']),
            $rawData['name'] ? new NodeName($rawData['name']) : null,
            new ContentStreamIdentifier($rawData['contentstreamidentifier']),
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
