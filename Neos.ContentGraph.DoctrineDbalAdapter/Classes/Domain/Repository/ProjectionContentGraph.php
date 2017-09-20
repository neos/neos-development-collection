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
use Neos\ContentGraph\Infrastructure\Dto\Node;
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
                ->executeQuery('SELECT count(*) FROM neos_contentgraph_hierarchyedge')
                ->fetch()['count'] > 0;
    }

    public function getNode(NodeIdentifier $nodeIdentifier, string $subgraphIdentifier): Node
    {
        $nodeData = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM neos_contentgraph_node n
 WHERE nodeidentifier = :nodeIdentifier
 AND subgraphidentifier = :subgraphIdentifier',
            [
                'nodeIdentifier' => $nodeIdentifier,
                'subgraphIdentifier' => $subgraphIdentifier
            ]
        )->fetch();

        return $this->mapRawDataToNode($nodeData);
    }

    public function getEdgePosition(string $parentIdentifier, string $elderSiblingIdentifier = null, string $subgraphIdentifier)
    {
        if ($elderSiblingIdentifier) {
            $elderSiblingPosition = (int)$this->getDatabaseConnection()->executeQuery(
                'SELECT h.position FROM neos_contentgraph_hierarchyedge h
 WHERE childnodesidentifieringraph = :elderSiblingIdentifier
 AND subgraphidentifier = :subgraphIdentifier',
                [
                    'elderSiblingIdentifier' => $elderSiblingIdentifier,
                    'subgraphIdentifier' => $subgraphIdentifier
                ]
            )->fetch()['position'];

            $youngerSiblingEdge = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyedge h
 WHERE parentnodesidentifieringraph = :parentNodesIdentifierInGraph
 AND subgraphidentifier = :subgraphIdentifier
 AND `position` > :position',
                [
                    'parentNodesIdentifierInGraph' => $parentIdentifier,
                    'subgraphIdentifier' => $subgraphIdentifier,
                    'position' => $elderSiblingPosition
                ]
            )->fetch();

            if (!is_null($youngerSiblingEdge['position'])) {
                $position = ($elderSiblingPosition + (int)$youngerSiblingEdge['position']) / 2;
            } else {
                $position = $elderSiblingPosition + 128;
            }
        } else {
            $eldestSiblingEdge = $this->getDatabaseConnection()->executeQuery(
                'SELECT MIN(h.position) AS `position` FROM neos_contentgraph_hierarchyedge h
 WHERE parentnodesidentifieringraph = :parentNodesIdentifierInGraph
 AND subgraphidentifier = :subgraphIdentifier
 ORDER BY `position` ASC',
                [
                    'parentNodesIdentifierInGraph' => $parentIdentifier,
                    'subgraphIdentifier' => $subgraphIdentifier
                ]
            )->fetch();

            if ($eldestSiblingEdge) {
                $position = ((int)$eldestSiblingEdge['position']) - 128;
            } else {
                $position = 0;
            }
        }

        return $position;
    }

    /**
     * @param string $parentNodesIdentifierInGraph
     * @param string $subgraphIdentifier
     * @return array|HierarchyEdge[]
     */
    public function getOutboundHierarchyEdgesForNodeAndSubgraph(string $parentNodesIdentifierInGraph, string $subgraphIdentifier): array
    {
        $edges = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyedge h
 WHERE parentnodesidentifieringraph = :parentNodesIdentifierInGraph
 AND subgraphidentifier = :subgraphIdentifier',
            [
                'parentNodesIdentifierInGraph' => $parentNodesIdentifierInGraph,
                'subgraphIdentifier' => $subgraphIdentifier
            ]
        )->fetchAll() as $edgeData) {
            $edges[] = $this->mapRawDataToHierarchyEdge($edgeData);
        }

        return $edges;
    }


    /**
     * @param string $childNodesIdentifierInGraph
     * @param array $subgraphIdentifiers
     * @return array|HierarchyEdge[]
     */
    public function findInboundHierarchyEdgesForNodeAndSubgraphs(string $childNodesIdentifierInGraph, array $subgraphIdentifiers): array
    {
        $edges = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyedge h
 WHERE childnodesidentifieringraph = :childNodesIdentifierInGraph
 AND subgraphidentifier IN (:subgraphIdentifiers)',
            [
                'childNodesIdentifierInGraph' => $childNodesIdentifierInGraph,
                'subgraphIdentifiers' => $subgraphIdentifiers
            ],
            [
                'subgraphIdentifiers' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAll() as $edgeData) {
            $edges[] = $this->mapRawDataToHierarchyEdge($edgeData);
        }

        return $edges;
    }

    /**
     * @param string $parentNodesIdentifierInGraph
     * @param array $subgraphIdentifiers
     * @return array|HierarchyEdge[]
     */
    public function findOutboundHierarchyEdgesForNodeAndSubgraphs(string $parentNodesIdentifierInGraph, array $subgraphIdentifiers): array
    {
        $edges = [];
        foreach ($this->getDatabaseConnection()->executeQuery(
            'SELECT h.* FROM neos_contentgraph_hierarchyedge h
 WHERE parentnodesidentifieringraph = :parentNodesIdentifierInGraph
 AND subgraphidentifier IN (:subgraphIdentifiers)',
            [
                'parentNodesIdentifierInGraph' => $parentNodesIdentifierInGraph,
                'subgraphIdentifiers' => $subgraphIdentifiers
            ],
            [
                'subgraphIdentifiers' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAll() as $edgeData) {
            $edges[] = $this->mapRawDataToHierarchyEdge($edgeData);
        }

        return $edges;
    }

    protected function mapRawDataToHierarchyEdge(array $rawData): HierarchyEdge
    {
        return new HierarchyEdge(
            $rawData['parentnodesidentifieringraph'],
            $rawData['childnodesidentifieringraph'],
            $rawData['name'],
            $rawData['subgraphidentifier'],
            $rawData['position']
        );
    }

    protected function mapRawDataToNode(array $rawData): Node
    {
        return new Node(
            $rawData['identifieringraph'],
            $rawData['identifierinsubgraph'],
            $rawData['subgraphidentifier'],
            json_decode($rawData['properties'], true),
            $rawData['nodetypename']
        );
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
