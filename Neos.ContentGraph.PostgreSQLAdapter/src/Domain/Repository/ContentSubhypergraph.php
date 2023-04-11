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

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphReferenceQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphSiblingQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphSiblingQueryMode;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\QueryUtility;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

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
 * @internal but the public {@see ContentSubgraphInterface} is API
 */
final class ContentSubhypergraph implements ContentSubgraphInterface
{
    public function __construct(
        private readonly ContentStreamId $contentStreamId,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly PostgresDbalClientInterface $databaseClient,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNamePrefix);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withNodeAggregateId($nodeAggregateId)
            ->withRestriction($this->visibilityConstraints);

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }

    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        FindChildNodesFilter $filter
    ): Nodes {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints);
        if (!is_null($filter->nodeTypeConstraints)) {
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $filter->nodeTypeConstraints,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeConstraints($nodeTypeConstraintsWithSubNodeTypes, 'cn');
        }
        if (!is_null($filter->pagination)) {
            $query = $query
                ->withLimit($filter->pagination->limit)
                ->withOffset($filter->pagination->offset);
        }

        $childNodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $childNodeRows,
            $this->visibilityConstraints
        );
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\CountChildNodesFilter $filter): int
    {
        // TODO: Implement countChildNodes() method.
        return 0;
    }

    public function findReferences(
        NodeAggregateId $nodeAggregateId,
        FindReferencesFilter $filter
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamId,
            'tarn.*, tarh.contentstreamid, tarh.dimensionspacepoint',
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withSourceNodeAggregateId($nodeAggregateId)
            ->withSourceRestriction($this->visibilityConstraints)
            ->withTargetRestriction($this->visibilityConstraints);

        $orderings = [];
        if ($filter->referenceName) {
            $query = $query->withReferenceName($filter->referenceName);
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

    public function countReferences(NodeAggregateId $nodeAggregateId, Filter\CountReferencesFilter $filter): int
    {
        // TODO: Implement countReferences() method.
        return 0;
    }

    public function findBackReferences(
        NodeAggregateId $nodeAggregateId,
        FindBackReferencesFilter $filter
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamId,
            'srcn.*, srch.contentstreamid, srch.dimensionspacepoint',
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withTargetNodeAggregateId($nodeAggregateId)
            ->withSourceRestriction($this->visibilityConstraints)
            ->withTargetRestriction($this->visibilityConstraints);

        $orderings = [];
        if ($filter->referenceName) {
            $query = $query->withReferenceName($filter->referenceName);
        } else {
            $orderings[] = 'r.name';
        }
        $orderings[] = 'r.position';
        $orderings[] = 'srcn.nodeaggregateid';
        $query = $query->orderedBy($orderings);

        $referenceRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->visibilityConstraints
        );
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, Filter\CountBackReferencesFilter $filter): int
    {
        // TODO: Implement countBackReferences() method.
        return 0;
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $query = HypergraphParentQuery::create($this->contentStreamId, $this->tableNamePrefix);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints)
            ->withChildNodeAggregateId($childNodeAggregateId);

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }

    public function findNodeByPath(
        NodePath $path,
        NodeAggregateId $startingNodeAggregateId
    ): ?Node {
        $currentNode = $this->findNodeById($startingNodeAggregateId);
        if (!$currentNode) {
            return null;
        }
        foreach ($path->getParts() as $edgeName) {
            $currentNode = $this->findChildNodeConnectedThroughEdgeName(
                $currentNode->nodeAggregateId,
                $edgeName
            );
            if (!$currentNode) {
                return null;
            }
        }

        return $currentNode;
    }

    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $edgeName
    ): ?Node {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
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

    public function findSucceedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        FindSucceedingSiblingNodesFilter $filter
    ): Nodes {
        return $this->findAnySiblings(
            $siblingNodeAggregateId,
            HypergraphSiblingQueryMode::MODE_ONLY_SUCCEEDING,
            $filter->nodeTypeConstraints,
            $filter->pagination,
        );
    }

    public function findPrecedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        FindPrecedingSiblingNodesFilter $filter
    ): Nodes {
        return $this->findAnySiblings(
            $siblingNodeAggregateId,
            HypergraphSiblingQueryMode::MODE_ONLY_PRECEDING,
            $filter->nodeTypeConstraints,
            $filter->pagination,
        );
    }

    private function findAnySiblings(
        NodeAggregateId $sibling,
        HypergraphSiblingQueryMode $mode,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        ?Pagination $pagination = null,
    ): Nodes {
        $query = HypergraphSiblingQuery::create(
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $sibling,
            $mode,
            $this->tableNamePrefix
        );
        $query = $query->withRestriction($this->visibilityConstraints);
        if (!is_null($nodeTypeConstraints)) {
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $nodeTypeConstraints,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeConstraints($nodeTypeConstraintsWithSubNodeTypes, 'sn');
        }
        $query = $query->withOrdinalityOrdering($mode->isOrderingToBeReversed());
        if (!is_null($pagination)) {
            $query = $query
                ->withLimit($pagination->limit)
                ->withOffset($pagination->offset);
        }

        $siblingsRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes($siblingsRows, $this->visibilityConstraints);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): NodePath
    {
        return NodePath::fromString('/');
    }

    public function findSubtree(
        NodeAggregateId $entryNodeAggregateId,
        FindSubtreeFilter $filter
    ): ?Subtree {
        $parameters = [
            'entryNodeAggregateId' => $entryNodeAggregateId->value,
            'contentStreamId' => (string)$this->contentStreamId,
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
            'maximumLevels' => $filter->maximumLevels
        ];

        $types = [];
        if ($filter->nodeTypeConstraints !== null) {
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $filter->nodeTypeConstraints,
                $this->nodeTypeManager
            );
            $nodeTypeConstraintsClause = QueryUtility::getNodeTypeConstraintsClause($nodeTypeConstraintsWithSubNodeTypes, 'cn', $parameters, $types);
        } else {
            $nodeTypeConstraintsClause = '';
        }

        $query = /** @lang PostgreSQL */
            '-- ContentSubhypergraph::findSubtree
    WITH RECURSIVE subtree AS (
        SELECT n.*, h.contentstreamid,
            h.dimensionspacepoint,
            \'ROOT\'::varchar AS parentNodeAggregateId,
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
        WHERE n.nodeaggregateid = :entryNodeAggregateId
            AND h.contentstreamid = :contentStreamId
	    	AND h.dimensionspacepointhash = :dimensionSpacePointHash
        ' . QueryUtility::getRestrictionClause($this->visibilityConstraints, $this->tableNamePrefix) . '
    UNION ALL
         -- --------------------------------
         -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
         -- --------------------------------
        SELECT cn.*, ch.contentstreamid,
            ch.dimensionspacepoint,
            p.nodeaggregateid as parentNodeAggregateId,
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
	 	    ch.contentstreamid = :contentStreamId
		    AND ch.dimensionspacepointhash = :dimensionSpacePointHash
		    ' . ($filter->maximumLevels !== null ? 'AND p.level + 1 <= :maximumLevels' : '') . '
            ' . QueryUtility::getRestrictionClause($this->visibilityConstraints, $this->tableNamePrefix, 'c') . '
		    ' . $nodeTypeConstraintsClause . '
    )
    SELECT * FROM subtree
    -- NOTE: it is crucially important that *inside* a single level, we
    -- additionally order by ordinality (i.e. sort order of the childnodeanchor list)
    -- to preserve node ordering when fetching subtrees.
    ORDER BY level DESC, ordinality ASC';



        $nodeRows = $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
            ->fetchAllAssociative();
        if ($nodeRows === []) {
            return null;
        }

        return $this->nodeFactory->mapNodeRowsToSubtree($nodeRows, $this->visibilityConstraints);
    }

    public function findDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        FindDescendantNodesFilter $filter
    ): Nodes {
        return Nodes::createEmpty();
    }


    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountDescendantNodesFilter $filter): int
    {
        // TODO: Implement countDescendantNodes() method.
        return 0;
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
            WHERE h.contentstreamid = :contentStreamId
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamId' => (string)$this->contentStreamId,
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
            'contentStreamId' => $this->contentStreamId,
            'dimensionSpacePoint' => $this->dimensionSpacePoint
        ];
    }
}
