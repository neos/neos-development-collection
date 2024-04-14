<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphAdapter;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeQueryBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphWithRuntimeCaches\ContentSubgraphWithRuntimeCaches;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * ## Conventions for SQL queries
 *
 *  - n -> node
 *  - h -> hierarchy edge
 *
 *  - if more than one node (parent-child)
 *    - pn -> parent node
 *    - cn -> child node
 *    - h -> the hierarchy edge connecting parent and child
 *    - ph -> the hierarchy edge incoming to the parent (sometimes relevant)
 *    - dsp -> dimension space point, resolves hashes to full dimension coordinates
 *    - cdsp -> child dimension space point, same as dsp for child queries
 *    - pdsp -> parent dimension space point, same as dsp for parent queries
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class ContentGraph implements ContentGraphInterface
{
    private readonly NodeQueryBuilder $nodeQueryBuilder;

    /**
     * @var array<string,ContentSubgraphWithRuntimeCaches>
     */
    private array $subgraphs = [];

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
        $this->nodeQueryBuilder = new NodeQueryBuilder($this->client->getConnection(), $this->tableNamePrefix);
    }

    final public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamId->value . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraphWithRuntimeCaches(
                new ContentSubgraph(
                    $this->contentRepositoryId,
                    $contentStreamId,
                    $dimensionSpacePoint,
                    $visibilityConstraints,
                    $this->client,
                    $this->nodeFactory,
                    $this->nodeTypeManager,
                    $this->tableNamePrefix
                )
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws RootNodeAggregateDoesNotExist
     */
    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            $contentStreamId,
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        $rootNodeAggregate = $rootNodeAggregates->first();

        if ($rootNodeAggregate === null) {
            throw RootNodeAggregateDoesNotExist::butWasExpectedTo($nodeTypeName);
        }

        return $rootNodeAggregate;
    }

    public function findRootNodeAggregates(
        ContentStreamId $contentStreamId,
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery();
        $queryBuilder
            ->andWhere('h.parentnodeanchor = :rootEdgeParentAnchorId')
            ->setParameters([
                'contentStreamId' => $contentStreamId->value,
                'rootEdgeParentAnchorId' => NodeRelationAnchorPoint::forRootEdge()->value,
            ]);

        if ($filter->nodeTypeName !== null) {
            $queryBuilder
                ->andWhere('n.nodetypename = :nodeTypeName')
                ->setParameter('nodeTypeName', $filter->nodeTypeName->value);
        }
        return NodeAggregates::fromArray(iterator_to_array($this->mapQueryBuilderToNodeAggregates($queryBuilder, $contentStreamId)));
    }

    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): iterable {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery();
        $queryBuilder
            ->andWhere('n.nodetypename = :nodeTypeName')
            ->setParameters([
                'contentStreamId' => $contentStreamId->value,
                'nodeTypeName' => $nodeTypeName->value,
            ]);
        return $this->mapQueryBuilderToNodeAggregates($queryBuilder, $contentStreamId);
    }

    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $contentGraphAdapter = new ContentGraphAdapter($this->client->getConnection(), $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, null, $contentStreamId);
        return $contentGraphAdapter->findNodeAggregateById($nodeAggregateId);
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $contentGraphAdapter = new ContentGraphAdapter($this->client->getConnection(), $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, null, $contentStreamId);
        return $contentGraphAdapter->findParentNodeAggregates($childNodeAggregateId);
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $contentGraphAdapter = new ContentGraphAdapter($this->client->getConnection(), $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, null, $contentStreamId);
        return $contentGraphAdapter->findChildNodeAggregates($parentNodeAggregateId);
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $contentGraphAdapter = new ContentGraphAdapter($this->client->getConnection(), $this->tableNamePrefix, $this->contentRepositoryId, $this->nodeFactory, $this->nodeTypeManager, null, $contentStreamId);
        return $contentGraphAdapter->findChildNodeAggregatesByName($parentNodeAggregateId, $name);
    }

    public function countNodes(): int
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->tableNamePrefix . '_node');
        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            throw new \RuntimeException(sprintf('Failed to count nodes. Expected result to be of type %s, got: %s', Result::class, get_debug_type($result)), 1701444550);
        }
        try {
            return (int)$result->fetchOne();
        } catch (DriverException | DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch rows from database: %s', $e->getMessage()), 1701444590, $e);
        }
    }

    public function findUsedNodeTypeNames(): iterable
    {
        return array_map(static fn (array $row) => NodeTypeName::fromString($row['nodetypename']), $this->nodeQueryBuilder->findUsedNodeTypeNames());
    }

    /**
     * @return ContentSubgraphWithRuntimeCaches[]
     * @internal only used for {@see DoctrineDbalContentGraphProjection}
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->client->getConnection()->createQueryBuilder();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return iterable<NodeAggregate>
     */
    private function mapQueryBuilderToNodeAggregates(QueryBuilder $queryBuilder, ContentStreamId $contentStreamId): iterable
    {
        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $this->fetchRows($queryBuilder),
            $contentStreamId,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchRows(QueryBuilder $queryBuilder): array
    {
        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            throw new \RuntimeException(sprintf('Failed to execute query. Expected result to be of type %s, got: %s', Result::class, get_debug_type($result)), 1701443535);
        }
        try {
            return $result->fetchAllAssociative();
        } catch (DriverException | DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch rows from database: %s', $e->getMessage()), 1701444358, $e);
        }
    }
}
