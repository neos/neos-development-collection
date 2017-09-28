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
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeAggregate;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
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
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return Node|null
     */
    public function getNode(NodeIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeidentifier = :nodeIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier       
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
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
     * @param NodeIdentifier $nodeIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Node|null
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
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $precedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     */
    public function getHierarchyRelationPosition(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $precedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int
    {
        if ($precedingSiblingAnchorPoint) {
            $precedingSiblingPosition = (int)$this->getDatabaseConnection()->executeQuery(
                'SELECT h.position FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.childnodeanchor = :precedingSiblingAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'precedingSiblingAnchorPoint' => (string)$precedingSiblingAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch()['position'];

            $succeedingSiblingRelation = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.parentnodeanchor = :parentAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          AND h.`position` > :position',
                [
                    'parentAnchorPoint' => $parentAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    'position' => $precedingSiblingPosition
                ]
            )->fetch();

            if (!is_null($succeedingSiblingRelation['position'])) {
                $position = ($precedingSiblingPosition + (int)$succeedingSiblingRelation['position']) / 2;
            } else {
                $position = $precedingSiblingPosition + 128;
            }
        } else {
            $leftmostPrecedingSiblingRelation = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.parentnodeanchor = :parentAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          ORDER BY h.`position` ASC',
                [
                    'parentAnchorPoint' => (string)$parentAnchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch();

            if ($leftmostPrecedingSiblingRelation) {
                $position = ((int)$leftmostPrecedingSiblingRelation['position']) - 128;
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
     * @return array|HierarchyRelation[]
     */
    public function getOutboundHierarchyRelationsForNodeAndSubgraph(NodeRelationAnchorPoint $parentAnchorPoint, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): array
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
     * @param string $childNodesIdentifierInGraph
     * @param array $subgraphIdentityHashs
     * @return array|HierarchyRelation[]
     */
    public function findInboundHierarchyRelationsForNodeAndSubgraphs(string $childNodesIdentifierInGraph, array $subgraphIdentityHashs): array
    {
        // TODO needs to be fixed
        $relations = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
 WHERE childnodesidentifieringraph = :childNodesIdentifierInGraph
 AND subgraphIdentityHash IN (:subgraphIdentityHashs)',
            [
                'childNodesIdentifierInGraph' => $childNodesIdentifierInGraph,
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
     * @param string $parentNodesIdentifierInGraph
     * @param array $subgraphIdentityHashs
     * @return array|HierarchyRelation[]
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
     * @param array $rawData
     * @return HierarchyRelation
     */
    protected function mapRawDataToHierarchyRelation(array $rawData): HierarchyRelation
    {
        return new HierarchyRelation(
            $rawData['parentnodeanchor'],
            $rawData['childnodeanchor'],
            $rawData['name'],
            $rawData['contentstreamidentifier'],
            json_decode($rawData['dimensionspacepoint'], true),
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

    /**
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return array
     */
    public function getAllContentStreamIdentifiersAnchorPointIsContainedIn(NodeRelationAnchorPoint $nodeRelationAnchorPoint) : array
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
}
