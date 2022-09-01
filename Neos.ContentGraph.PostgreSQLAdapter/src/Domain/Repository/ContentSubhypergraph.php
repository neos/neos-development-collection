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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencedNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;

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
        private readonly ContentStreamId $contentStreamIdentifier,
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
        $query = HypergraphQuery::create($this->contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withNodeAggregateIdentifier($nodeAggregateId)
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
            $this->contentStreamIdentifier,
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
        if (!is_null($filter->limit)) {
            $query = $query->withLimit($filter->limit);
        }
        if (!is_null($filter->offset)) {
            $query = $query->withOffset($filter->offset);
        }

        $childNodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $childNodeRows,
            $this->visibilityConstraints
        );
    }

    public function findReferencedNodes(
        NodeAggregateId $nodeAggregateId,
        FindReferencedNodesFilter $filter
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamIdentifier,
            'tarn.*, tarh.contentstreamidentifier, tarh.dimensionspacepoint',
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withSourceNodeAggregateIdentifier($nodeAggregateId)
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

    public function findReferencingNodes(
        NodeAggregateId $nodeAggregateId,
        FindReferencingNodesFilter $filter
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamIdentifier,
            'srcn.*, srch.contentstreamidentifier, srch.dimensionspacepoint',
            $this->tableNamePrefix
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withTargetNodeAggregateIdentifier($nodeAggregateId)
            ->withSourceRestriction($this->visibilityConstraints);

        $orderings = [];
        if ($filter->referenceName) {
            $query = $query->withReferenceName($filter->referenceName);
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

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $query = HypergraphParentQuery::create($this->contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withChildNodeAggregateIdentifier($childNodeAggregateId);

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
            throw new \RuntimeException(
                'Starting Node (identified by ' . $startingNodeAggregateId . ') does not exist.'
            );
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
            $this->contentStreamIdentifier,
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

    public function findSucceedingSiblings(
        NodeAggregateId $sibling,
        FindSucceedingSiblingsFilter $filter
    ): Nodes {
        return $this->findAnySiblings(
            $sibling,
            HypergraphSiblingQueryMode::MODE_ONLY_SUCCEEDING,
            $filter->nodeTypeConstraints,
            $filter->limit,
            $filter->offset
        );
    }

    public function findPrecedingSiblings(
        NodeAggregateId $sibling,
        FindPrecedingSiblingsFilter $filter
    ): Nodes {
        return $this->findAnySiblings(
            $sibling,
            HypergraphSiblingQueryMode::MODE_ONLY_PRECEDING,
            $filter->nodeTypeConstraints,
            $filter->limit,
            $filter->offset
        );
    }

    private function findAnySiblings(
        NodeAggregateId $sibling,
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
            $nodeTypeConstraintsWithSubNodeTypes = NodeTypeConstraintsWithSubNodeTypes::create(
                $nodeTypeConstraints,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeConstraints($nodeTypeConstraintsWithSubNodeTypes, 'sn');
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

    public function findNodePath(NodeAggregateId $nodeAggregateId): NodePath
    {
        return NodePath::fromString('/');
    }

    public function findSubtrees(
        NodeAggregateIds $entryNodeAggregateIds,
        FindSubtreesFilter $filter
    ): Subtrees {
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
            'entryNodeAggregateIdentifiers' => $entryNodeAggregateIds->toStringArray(),
            'contentStreamIdentifier' => (string)$this->contentStreamIdentifier,
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash,
            'maximumLevels' => $filter->maximumLevels
        ];

        $types = [
            'entryNodeAggregateIdentifiers' => Connection::PARAM_STR_ARRAY
        ];

        $nodeRows = $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
            ->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToSubtree($nodeRows, $this->visibilityConstraints);
    }

    public function findDescendants(
        NodeAggregateIds $entryNodeAggregateIds,
        FindDescendantsFilter $filter
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
