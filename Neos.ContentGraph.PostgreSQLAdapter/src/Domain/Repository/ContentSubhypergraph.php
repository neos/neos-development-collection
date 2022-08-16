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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

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
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Projection\ContentGraph\References;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
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
 * @api
 */
#[Flow\Proxy(false)]
final class ContentSubhypergraph implements ContentSubgraphInterface
{
    public function __construct(
        private readonly ContentStreamIdentifier $contentStreamIdentifier,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly PostgresDbalClientInterface $databaseClient,
        private readonly NodeFactory $nodeFactory,
        private readonly string $tableNamePrefix
    ) {
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
        $query = HypergraphQuery::create($this->contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withNodeAggregateIdentifier($nodeAggregateIdentifier)
            ->withRestriction($this->visibilityConstraints);

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
            $parentNodeAggregateIdentifier,
            $this->tableNamePrefix
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
            $this->tableNamePrefix,
            ['COUNT(*)']
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints);
        if (!is_null($nodeTypeConstraints)) {
            $query = $query->withNodeTypeConstraints($nodeTypeConstraints, 'cn');
        }

        $result = $query->execute($this->getDatabaseConnection())->fetchNumeric();

        return $result ? $result[0] : 0;
    }

    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateAggregateIdentifier,
        PropertyName $name = null
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamIdentifier,
            'tarn.*, tarh.contentstreamidentifier, tarh.dimensionspacepoint',
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withSourceNodeAggregateIdentifier($nodeAggregateAggregateIdentifier)
            ->withTargetRestriction($this->visibilityConstraints);

        $orderings = [];
        if ($name) {
            $query = $query->withReferenceName($name);
        } else {
            $orderings[] = 'r.name';
        }
        $orderings[] = 'r.position';
        $query = $query->orderedBy($orderings);

        $referenceRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->visibilityConstraints
        );
    }

    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamIdentifier,
            'srcn.*, srch.contentstreamidentifier, srch.dimensionspacepoint',
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withTargetNodeAggregateIdentifier($nodeAggregateIdentifier)
            ->withSourceRestriction($this->visibilityConstraints);

        $orderings = [];
        if ($name) {
            $query = $query->withReferenceName($name);
        } else {
            $orderings[] = 'r.name';
        }
        $orderings[] = 'r.position';
        $orderings[] = 'srcn.nodeaggregateidentifier';
        $query = $query->orderedBy($orderings);

        $referenceRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->visibilityConstraints
        );
    }

    public function findParentNode(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface
    {
        $query = HypergraphParentQuery::create($this->contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withChildNodeAggregateIdentifier($childNodeAggregateIdentifier);

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
            $parentNodeAggregateIdentifier,
            $this->tableNamePrefix,
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints)
            ->withChildNodeName($edgeName);

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
        );
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
            $mode,
            $this->tableNamePrefix
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
        $query = $query->withOrdinalityOrdering($mode->isOrderingToBeReversed());

        $siblingsRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes($siblingsRows, $this->visibilityConstraints);
    }

    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath
    {
        return NodePath::fromString('/');
    }

    public function findSubtrees(
        NodeAggregateIdentifiers $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        $query = /** @lang PostgreSQL */ '-- ContentSubhypergraph::findSubtrees
    WITH RECURSIVE subtree AS (
        SELECT n.*, h.contentstreamidentifier,
            h.dimensionspacepoint,
            \'ROOT\'::varchar AS parentNodeAggregateIdentifier,
            0 as level,
            h.ordinality
        FROM ' . $this->tableNamePrefix . '_node n
            INNER JOIN (
                SELECT *
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation,
                     -- this creates a new generated column "ordinality" which contains the sorting
                     -- order of the childnodeanchor entries. We use this on the top level query to
                     -- ensure that we preserve sorting of child nodes.
                     unnest(childnodeanchors) WITH ORDINALITY childnodeanchor
            ) h ON n.relationanchorpoint = h.childnodeanchor
        WHERE n.nodeaggregateidentifier IN (:entryNodeAggregateIdentifiers)
            AND h.contentstreamidentifier = :contentStreamIdentifier
	    	AND h.dimensionspacepointhash = :dimensionSpacePointHash
        ' . QueryUtility::getRestrictionClause($this->visibilityConstraints, $this->tableNamePrefix) . '
    UNION ALL
         -- --------------------------------
         -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
         -- --------------------------------
        SELECT cn.*, ch.contentstreamidentifier,
            ch.dimensionspacepoint,
            p.nodeaggregateidentifier as parentNodeAggregateIdentifier,
            p.level + 1 as level,
            ch.ordinality
        FROM subtree p
            INNER JOIN (
                SELECT *
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation,
                     -- this creates a new generated column "ordinality" which contains the sorting
                     -- order of the childnodeanchor entries. We use this on the top level query to
                     -- ensure that we preserve sorting of child nodes.
                     unnest(childnodeanchors) WITH ORDINALITY childnodeanchor
            ) ch ON ch.parentnodeanchor = p.relationanchorpoint
            INNER JOIN ' . $this->tableNamePrefix . '_node cn ON cn.relationanchorpoint = ch.childnodeanchor
	    WHERE
	 	    ch.contentstreamidentifier = :contentStreamIdentifier
		    AND ch.dimensionspacepointhash = :dimensionSpacePointHash
		    AND p.level + 1 <= :maximumLevels
            ' . QueryUtility::getRestrictionClause($this->visibilityConstraints, $this->tableNamePrefix, 'c') . '
		    -- @todo node type constraints
    )
    SELECT * FROM subtree
    -- NOTE: it is crucially important that *inside* a single level, we
    -- additionally order by ordinality (i.e. sort order of the childnodeanchor list)
    -- to preserve node ordering when fetching subtrees.
    ORDER BY level DESC, ordinality ASC';

        $parameters = [
            'entryNodeAggregateIdentifiers' => $entryNodeAggregateIdentifiers->toStringArray(),
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
        return Nodes::createEmpty();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = /** @lang PostgreSQL */
        'SELECT COUNT(*)
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchNumeric();

        return $result ? $result[0] : 0;
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
