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
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\Content\NodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @var array<string,ContentSubgraphInterface>
     */
    private array $subgraphs = [];

    /**
     * @param DbalClientInterface $client
     * @param NodeFactory $nodeFactory
     */
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory
    ) {
    }

    final public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamIdentifier . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraph(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->client,
                $this->nodeFactory
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws DBALException
     * @throws NodeTypeNotFoundException
     */
    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface {
        $connection = $this->client->getConnection();

        // HINT: we check the ContentStreamIdentifier on the EDGE;
        // as this is where we actually find out whether the node exists in the content stream
        $nodeRow = $connection->executeQuery(
            'SELECT n.*, h.contentstreamidentifier, h.name FROM neos_contentgraph_node n
                  INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                  WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                  AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
                  AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        ) : null;
    }

    /**
     * @throws \Exception
     */
    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                    FROM neos_contentgraph_node n
                        JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.parentnodeanchor = :rootEdgeParentAnchorIdentifier
                        AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'rootEdgeParentAnchorIdentifier' => (string)NodeRelationAnchorPoint::forRootEdge(),
            'nodeTypeName' => (string)$nodeTypeName,
        ];

        $nodeRow = $connection->executeQuery($query, $parameters)->fetch();

        if (!is_array($nodeRow)) {
            throw new \RuntimeException('Root Node Aggregate not found');
        }

        /** @var NodeAggregate $nodeAggregate The factory will return a NodeAggregate since the array is not empty */
        $nodeAggregate = $this->nodeFactory->mapNodeRowsToNodeAggregate(
            [$nodeRow],
            VisibilityConstraints::withoutRestrictions()
        );

        return $nodeAggregate;
    }

    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                FROM neos_contentgraph_node n
                    JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'nodeTypeName' => (string)$nodeTypeName,
        ];

        $resultStatement = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $resultStatement,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamidentifier, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM neos_contentgraph_hierarchyrelation h
                      JOIN neos_contentgraph_node n ON n.relationanchorpoint = h.childnodeanchor
                      LEFT JOIN neos_contentgraph_restrictionrelation r
                          ON r.originnodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.contentstreamidentifier = h.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT p.*,
                      ph.name, ph.contentstreamidentifier, ph.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM neos_contentgraph_node p
                      JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN neos_contentgraph_restrictionrelation r
                          ON r.originnodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.contentstreamidentifier = ph.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE c.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamidentifier, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM neos_contentgraph_node n
                      JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      LEFT JOIN neos_contentgraph_restrictionrelation r
                          ON r.originnodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.contentstreamidentifier = h.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateidentifier = (
                          SELECT p.nodeaggregateidentifier FROM neos_contentgraph_node p
                          INNER JOIN neos_contentgraph_hierarchyrelation ch
                              ON ch.parentnodeanchor = p.relationanchorpoint
                          INNER JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                          WHERE ch.contentstreamidentifier = :contentStreamIdentifier
                          AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                          AND c.nodeaggregateidentifier = :childNodeAggregateIdentifier
                          AND c.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                      )
                      AND h.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|\Exception
     */
    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery();

        $parameters = [
            'parentNodeAggregateIdentifier' => (string) $parentNodeAggregateIdentifier,
            'contentStreamIdentifier' => (string) $contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|NodeTypeNotFoundException
     */
    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND ch.name = :relationName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'relationName' => (string)$name
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|NodeTypeNotFoundException
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND c.classification = :tetheredClassification';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'tetheredClassification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    private function createChildNodeAggregateQuery(): string
    {
        return 'SELECT c.*,
                      ch.name, ch.contentstreamidentifier, ch.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM neos_contentgraph_node p
                      JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN neos_contentgraph_restrictionrelation r
                          ON r.originnodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.contentstreamidentifier = ph.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = p.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE p.nodeaggregateidentifier = :parentNodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeName $nodeName
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     * @throws DBALException
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $connection = $this->client->getConnection();

        $query = 'SELECT h.dimensionspacepoint, h.dimensionspacepointhash FROM neos_contentgraph_hierarchyrelation h
                      INNER JOIN neos_contentgraph_node n ON h.parentnodeanchor = n.relationanchorpoint
                      INNER JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateidentifier = :parentNodeAggregateIdentifier
                      AND n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                      AND h.name = :nodeName';
        $parameters = [
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
            'contentStreamIdentifier' => (string) $contentStreamIdentifier,
            'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
            'nodeName' => (string) $nodeName
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];
        $dimensionSpacePoints = [];
        foreach ($connection->executeQuery($query, $parameters, $types)
                     ->fetchAll() as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']]
                = DimensionSpacePoint::fromJsonString($hierarchyRelationData['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function countNodes(): int
    {
        $connection = $this->client->getConnection();
        $query = 'SELECT COUNT(*) FROM neos_contentgraph_node';

        return (int) $connection->executeQuery($query)->fetch()['COUNT(*)'];
    }

    public function findUsedNodeTypeNames(): iterable
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery('SELECT DISTINCT nodetypename FROM neos_contentgraph_node')->fetchAll();
        return array_map(function (array $row) {
            return NodeTypeName::fromString($row['nodetypename']);
        }, $rows);
    }

    public function enableCache(): void
    {
        foreach ($this->subgraphs as $subgraph) {
            $subgraph->getInMemoryCache()->enable();
        }
    }

    public function disableCache(): void
    {
        foreach ($this->subgraphs as $subgraph) {
            $subgraph->getInMemoryCache()->disable();
        }
    }
}
