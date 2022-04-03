<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphReferenceQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphSiblingQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphSiblingQueryMode;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\QueryUtility;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\DbalClient;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 * ## Conventions for SQL queries
 *
 * - n -> node
 * - h -> hierarchy hyperrelation
 *
 * - if more than one node (parent-child)
 *   - pn -> parent node
 *   - cn -> child node
 *   - h -> the hierarchy hyperrelation connecting parent and children
 *   - ph -> the hierarchy hyperrelation incoming to the parent (sometimes relevant)
 *
 * @Flow\Proxy(false)
 * @api
 */
final class ContentSubhypergraph implements ContentSubgraphInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private DimensionSpacePoint $dimensionSpacePoint;

    private VisibilityConstraints $visibilityConstraints;

    private DbalClient $databaseClient;

    private NodeFactory $nodeFactory;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints,
        DbalClient $databaseClient,
        NodeFactory $nodeFactory
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->visibilityConstraints = $visibilityConstraints;
        $this->databaseClient = $databaseClient;
        $this->nodeFactory = $nodeFactory;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        $query = HypergraphQuery::create($this->contentStreamIdentifier);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withNodeAggregateIdentifier($nodeAggregateIdentifier)
            ->withRestriction($this->visibilityConstraints);

        /** @phpstan-ignore-next-line @todo check actual return type */
        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }

    public function findChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        $query = HypergraphChildQuery::create(
            $this->contentStreamIdentifier,
            $parentNodeAggregateIdentifier
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints);
        if (!is_null($nodeTypeConstraints)) {
            $query = $query->withNodeTypeConstraints($nodeTypeConstraints, 'cn');
        }
        if (!is_null($limit)) {
            $query = $query->withLimit($limit);
        }
        if (!is_null($offset)) {
            $query = $query->withOffset($offset);
        }

        /** @phpstan-ignore-next-line @todo check actual return type */
        $childNodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $childNodeRows,
            $this->visibilityConstraints
        );
    }

    public function countChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int {
        $query = HypergraphChildQuery::create(
            $this->contentStreamIdentifier,
            $parentNodeAggregateIdentifier,
            ['COUNT(*)']
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints);
        if (!is_null($nodeTypeConstraints)) {
            $query = $query->withNodeTypeConstraints($nodeTypeConstraints, 'cn');
        }

        /** @phpstan-ignore-next-line @todo check actual return type */
        $result = $query->execute($this->getDatabaseConnection())->fetchNumeric();

        return $result[0];
    }

    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateAggregateIdentifier,
        PropertyName $name = null
    ): Nodes {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamIdentifier,
            'destn.*, desth.contentstreamidentifier, desth.dimensionspacepoint'
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withOriginNodeAggregateIdentifier($nodeAggregateAggregateIdentifier)
            ->withDestinationRestriction($this->visibilityConstraints);
        if ($name) {
            $query = $query->withReferenceName($name);
        }
        $query = $query->ordered();

        /** @phpstan-ignore-next-line @todo check actual return type */
        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes($nodeRows, $this->visibilityConstraints);
    }

    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ): Nodes {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamIdentifier,
            'orgn.*, orgh.contentstreamidentifier, orgh.dimensionspacepoint'
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withDestinationNodeAggregateIdentifier($nodeAggregateIdentifier)
            ->withOriginRestriction($this->visibilityConstraints);
        if ($name) {
            $query = $query->withReferenceName($name);
        }
        $query = $query->ordered();

        /** @phpstan-ignore-next-line @todo check actual return type */
        $referencedNodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $referencedNodeRows,
            $this->visibilityConstraints
        );
    }

    public function findParentNode(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface
    {
        $query = HypergraphParentQuery::create($this->contentStreamIdentifier);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withChildNodeAggregateIdentifier($childNodeAggregateIdentifier);

        /** @phpstan-ignore-next-line @todo check actual return type */
        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }

    public function findNodeByPath(
        NodePath $path,
        NodeAggregateIdentifier $startingNodeAggregateIdentifier
    ): ?NodeInterface {
        $currentNode = $this->findNodeByNodeAggregateIdentifier($startingNodeAggregateIdentifier);
        if (!$currentNode) {
            throw new \RuntimeException(
                'Starting Node (identified by ' . $startingNodeAggregateIdentifier . ') does not exist.'
            );
        }
        foreach ($path->getParts() as $edgeName) {
            $currentNode = $this->findChildNodeConnectedThroughEdgeName(
                $currentNode->getNodeAggregateIdentifier(),
                $edgeName
            );
            if (!$currentNode) {
                return null;
            }
        }

        return $currentNode;
    }

    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $edgeName
    ): ?NodeInterface {
        $query = HypergraphChildQuery::create(
            $this->contentStreamIdentifier,
            $parentNodeAggregateIdentifier
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints)
            ->withChildNodeName($edgeName);

        /** @phpstan-ignore-next-line @todo check actual return type */
        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint,
        ) : null;
    }

    public function findSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        return $this->findAnySiblings(
            $sibling,
            HypergraphSiblingQueryMode::MODE_ALL,
            $nodeTypeConstraints,
            $limit,
            $offset
        );
    }

    public function findSucceedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        return $this->findAnySiblings(
            $sibling,
            HypergraphSiblingQueryMode::MODE_ONLY_SUCCEEDING,
            $nodeTypeConstraints,
            $limit,
            $offset
        );
    }

    public function findPrecedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        return $this->findAnySiblings(
            $sibling,
            HypergraphSiblingQueryMode::MODE_ONLY_PRECEDING,
            $nodeTypeConstraints,
            $limit,
            $offset
        )->reverse();
    }

    private function findAnySiblings(
        NodeAggregateIdentifier $sibling,
        HypergraphSiblingQueryMode $mode,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        $query = HypergraphSiblingQuery::create(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $sibling,
            $mode
        );
        $query = $query->withRestriction($this->visibilityConstraints);
        if (!is_null($nodeTypeConstraints)) {
            $query = $query->withNodeTypeConstraints($nodeTypeConstraints, 'sn');
        }
        if (!is_null($limit)) {
            $query = $query->withLimit($limit);
        }
        if (!is_null($offset)) {
            $query = $query->withOffset($offset);
        }

        /** @phpstan-ignore-next-line @todo check actual return type */
        $siblingsRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes($siblingsRows, $this->visibilityConstraints);
    }

    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath
    {
        return NodePath::fromString('/');
    }

    public function findSubtrees(
        array $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        $query = /** @lang PostgreSQL */ '-- ContentSubhypergraph::findSubtrees
    WITH RECURSIVE subtree AS (
        SELECT n.*, h.contentstreamidentifier,
            h.dimensionspacepoint,
            \'ROOT\'::varchar AS parentNodeAggregateIdentifier,
            0 as level
        FROM ' . NodeRecord::TABLE_NAME . ' n
            INNER JOIN (
                SELECT *, unnest(childnodeanchors) AS childnodeanchor
                FROM ' . HierarchyHyperrelationRecord::TABLE_NAME . '
            ) h ON n.relationanchorpoint = h.childnodeanchor
        WHERE n.nodeaggregateidentifier IN (:entryNodeAggregateIdentifiers)
            AND h.contentstreamidentifier = :contentStreamIdentifier
	    	AND h.dimensionspacepointhash = :dimensionSpacePointHash
        ' . QueryUtility::getRestrictionClause($this->visibilityConstraints) . '
    UNION ALL
         -- --------------------------------
         -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
         -- --------------------------------
        SELECT cn.*, ch.contentstreamidentifier,
            ch.dimensionspacepoint,
            p.nodeaggregateidentifier as parentNodeAggregateIdentifier,
     	    p.level + 1 as level
        FROM subtree p
            INNER JOIN (
                SELECT *, unnest(childnodeanchors) AS childnodeanchor
                FROM ' . HierarchyHyperrelationRecord::TABLE_NAME . '
            ) ch ON ch.parentnodeanchor = p.relationanchorpoint
            INNER JOIN ' . NodeRecord::TABLE_NAME . ' cn ON cn.relationanchorpoint = ch.childnodeanchor
	    WHERE
	 	    ch.contentstreamidentifier = :contentStreamIdentifier
		    AND ch.dimensionspacepointhash = :dimensionSpacePointHash
		    AND p.level + 1 <= :maximumLevels
            ' . QueryUtility::getRestrictionClause($this->visibilityConstraints, 'c') .'
		    -- @todo node type constraints
    )
    SELECT * FROM subtree
    ORDER BY level DESC';

        $parameters = [
            'entryNodeAggregateIdentifiers' => $entryNodeAggregateIdentifiers,
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
            'maximumLevels' => $maximumLevels
        ];

        $types = [
            'entryNodeAggregateIdentifiers' => Connection::PARAM_STR_ARRAY
        ];

        $nodeRows = $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
            ->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToSubtree($nodeRows, $this->visibilityConstraints);
    }

    public function findDescendants(
        array $entryNodeAggregateIdentifiers,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes {
        return Nodes::empty();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = /** @lang PostgreSQL */
        'SELECT COUNT(*)
            FROM ' . HierarchyHyperrelationRecord::TABLE_NAME .' h
            JOIN ' . NodeRecord::TABLE_NAME .' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchNumeric();

        return $result ? $result[0] : 0;
    }

    public function getInMemoryCache(): InMemoryCache
    {
        return new InMemoryCache();
    }

    private function getDatabaseConnection(): DatabaseConnection
    {
        return $this->databaseClient->getConnection();
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint
        ];
    }
}
