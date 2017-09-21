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
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Dto\HierarchyEdge;
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\ContentGraph\Domain\Projection\Node;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
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

    public function getNode(NodeIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): Node
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 WHERE n.nodeidentifier = :nodeIdentifier
 AND n.contentstreamidentifier = :contentStreamIdentifier
 AND n.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'nodeIdentifier' => (string)$nodeIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
            ]
        )->fetch();

        return $this->mapRawDataToNode($nodeData);
    }

    public function getEdgePosition(NodeIdentifier $parentIdentifier, NodeIdentifier $precedingSiblingIdentifier = null, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint)
    {
        if ($precedingSiblingIdentifier) {
            $precedingSiblingPosition = (int)$this->getDatabaseConnection()->executeQuery(
                'SELECT h.position FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.childnodeidentifier = :precedingSiblingIdentifier
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'precedingSiblingIdentifier' => (string)$precedingSiblingIdentifier,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch()['position'];

            $youngerSiblingEdge = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.parentnodeidentifier = :parentNodeIdentifier
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          AND h.`position` > :position',
                [
                    'parentNodeIdentifier' => (string)$parentIdentifier,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash(),
                    'position' => $precedingSiblingPosition
                ]
            )->fetch();

            if (!is_null($youngerSiblingEdge['position'])) {
                $position = ($precedingSiblingPosition + (int)$youngerSiblingEdge['position']) / 2;
            } else {
                $position = $precedingSiblingPosition + 128;
            }
        } else {
            $leftmostPrecedingSiblingEdge = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyrelation h
                          WHERE h.parentnodeidentifier = :parentNodeIdentifier
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          ORDER BY h.`position` ASC',
                [
                    'parentNodeIdentifier' => (string)$parentIdentifier,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
                ]
            )->fetch();

            if ($leftmostPrecedingSiblingEdge) {
                $position = ((int)$leftmostPrecedingSiblingEdge['position']) - 128;
            } else {
                $position = 0;
            }
        }

        return $position;
    }

    /**
     * @param string $parentIdentifier
     * @param string $subgraphIdentityHash
     * @return array|HierarchyEdge[]
     */
    public function getOutboundHierarchyEdgesForNodeAndSubgraph(NodeIdentifier $parentIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): array
    {
        $edges = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyrelation h
                      WHERE h.parentnodeidentifier = :parentIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'parentIdentifier' => (string)$parentIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'dimensionSpacePointHash' => $dimensionSpacePoint->getHash()
            ]
        )->fetchAll() as $edgeData) {
            $edges[] = $this->mapRawDataToHierarchyEdge($edgeData);
        }

        return $edges;
    }


    /**
     * @param string $childNodesIdentifierInGraph
     * @param array $subgraphIdentityHashs
     * @return array|HierarchyEdge[]
     */
    public function findInboundHierarchyEdgesForNodeAndSubgraphs(string $childNodesIdentifierInGraph, array $subgraphIdentityHashs): array
    {
        // TODO needs to be fixed
        $edges = [];
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
        )->fetchAll() as $edgeData) {
            $edges[] = $this->mapRawDataToHierarchyEdge($edgeData);
        }

        return $edges;
    }

    /**
     * @param string $parentNodesIdentifierInGraph
     * @param array $subgraphIdentityHashs
     * @return array|HierarchyEdge[]
     */
    public function findOutboundHierarchyEdgesForNodeAndSubgraphs(string $parentNodesIdentifierInGraph, array $subgraphIdentityHashs): array
    {
        // TODO needs to be fixed
        $edges = [];
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
        )->fetchAll() as $edgeData) {
            $edges[] = $this->mapRawDataToHierarchyEdge($edgeData);
        }

        return $edges;
    }

    protected function mapRawDataToHierarchyEdge(array $rawData): HierarchyEdge
    {
        return new HierarchyEdge(
            $rawData['parentnodeidentifier'],
            $rawData['childnodeidentifier'],
            $rawData['name'],
            $rawData['contentstreamidentifier'],
            json_decode($rawData['dimensionspacepoint'], true),
            $rawData['dimensionspacepointhash'],
            $rawData['position']
        );
    }

    protected function mapRawDataToNode(array $rawData): Node
    {
        return new Node(
            $rawData['nodeidentifier'],
            $rawData['nodeaggregateidentifier'],
            $rawData['contentstreamidentifier'],
            json_decode($rawData['dimensionspacepoint'], true),
            $rawData['dimensionspacepointhash'],
            json_decode($rawData['properties'], true),
            $rawData['nodetypename']
        );
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
