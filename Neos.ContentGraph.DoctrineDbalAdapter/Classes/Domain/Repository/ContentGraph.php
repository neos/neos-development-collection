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
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

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
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var array|ContentSubgraphInterface[]
     */
    protected $subgraphs;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    final public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        Domain\Context\Parameters\VisibilityConstraints $visibilityConstraints
    ): ?ContentSubgraphInterface {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash() . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraph($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints);
        }

        return $this->subgraphs[$index];
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @throws DBALException
     * @throws \Exception
     * @return NodeAggregate|null
     */
    public function findRootNodeAggregateByType(ContentStreamIdentifier $contentStreamIdentifier, NodeTypeName $nodeTypeName): NodeAggregate
    {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint AS covereddimensionspacepoint FROM neos_contentgraph_node n
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

        return $this->nodeFactory->mapNodeRowsToNodeAggregate([$nodeRow]);
    }

    public function findNodeAggregatesByType(ContentStreamIdentifier $contentStreamIdentifier, NodeTypeName $nodeTypeName): iterable
    {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint AS covereddimensionspacepoint FROM neos_contentgraph_node n
                      JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      WHERE h.contentstreamidentifier = :contentStreamIdentifier
                      AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'nodeTypeName' => (string)$nodeTypeName,
        ];

        $resultStatement = $connection->executeQuery($query, $parameters);
        while ($nodeRow = $resultStatement->fetch()) {
            yield $this->nodeFactory->mapNodeRowsToNodeAggregate([$nodeRow]);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAggregate|null
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

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return iterable<NodeAggregate>
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
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
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $childOriginDimensionSpacePoint
     * @return NodeAggregate|null
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
                          INNER JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
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
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->getHash(),
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @return iterable<NodeAggregate>
     * @throws DBALException
     * @throws \Exception
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

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $name
     * @return iterable<NodeAggregate>
     * @throws DBALException
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

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @return iterable<NodeAggregate>
     * @throws DBALException
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
            'tetheredClassification' => (string)NodeAggregateClassification::tethered()
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
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
            'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->getHash(),
            'contentStreamIdentifier' => (string) $contentStreamIdentifier,
            'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
            'nodeName' => (string) $nodeName
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];
        $dimensionSpacePoints = [];
        foreach ($connection->executeQuery($query, $parameters, $types)->fetchAll() as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = new DimensionSpacePoint(json_decode($hierarchyRelationData['dimensionspacepoint'], true));
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function countNodes(): int
    {
        $connection = $this->client->getConnection();
        $query = 'SELECT COUNT(*) FROM neos_contentgraph_node';

        return (int) $connection->executeQuery($query)->fetch()['COUNT(*)'];
    }

    /**
     * Returns all content stream identifiers
     *
     * @return ContentStreamIdentifier[]
     */
    public function findProjectedContentStreamIdentifiers(): array
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery('SELECT DISTINCT contentstreamidentifier FROM neos_contentgraph_hierarchyrelation')->fetchAll();
        return array_map(function (array $row) {
            return ContentStreamIdentifier::fromString($row['contentstreamidentifier']);
        }, $rows);
    }

    public function findProjectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        $records = $this->client->getConnection()->executeQuery(
            'SELECT DISTINCT dimensionspacepoint FROM neos_contentgraph_hierarchyrelation'
        )->fetchAll();

        $records = array_map(function (array $record) {
            return DimensionSpacePoint::fromJsonString($record['dimensionspacepoint']);
        }, $records);

        return new DimensionSpacePointSet($records);
    }

    public function findProjectedNodeAggregateIdentifiersInContentStream(ContentStreamIdentifier $contentStreamIdentifier): array
    {
        $records = $this->client->getConnection()->executeQuery(
            'SELECT DISTINCT nodeaggregateidentifier FROM neos_contentgraph_node'
        )->fetchAll();

        return array_map(function (array $record) {
            return NodeAggregateIdentifier::fromString($record['nodeaggregateidentifier']);
        }, $records);
    }


    public function findProjectedNodeTypes(): iterable
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery('SELECT DISTINCT nodetypename FROM neos_contentgraph_node')->fetchAll();
        return array_map(function (array $row) {
            return NodeTypeName::fromString($row['nodetypename']);
        }, $rows);
    }

    public function enableCache(): void
    {
        if (is_array($this->subgraphs)) {
            foreach ($this->subgraphs as $subgraph) {
                $subgraph->getInMemoryCache()->enable();
            }
        }
    }

    public function disableCache(): void
    {
        if (is_array($this->subgraphs)) {
            foreach ($this->subgraphs as $subgraph) {
                $subgraph->getInMemoryCache()->disable();
            }
        }
    }
}
