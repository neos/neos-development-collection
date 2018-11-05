<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Doctrine\ORM\QueryBuilder;

class NodeDataQuery
{
    /**
     * Workspace to search
     *
     * @var Workspace
     */
    protected $workspace;

    /**
     * Workspace and base workspaces to search
     *
     * @var Workspace[]
     */
    protected $workspaces;

    /**
     * Dimensions to search
     *
     * @var string[]
     */
    protected $dimensions;

    /**
     * Filters to apply (WHERE)
     *
     * @var NodeDataFilterInterface[]
     */
    protected $filters = [];

    /**
     * Orders to apply (ORDER BY)
     *
     * @var NodeDataOrderInterface[]
     */
    protected $orders = [];

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @param Workspace $workspace
     * @param array $dimensions
     */
    public function __construct(Workspace $workspace, array $dimensions = [])
    {
        $this->workspace = $workspace;
        $this->dimensions = $dimensions;
    }

    public function initializeObject(): void
    {
        $this->collectBaseWorkspaces();
    }

    protected function collectBaseWorkspaces(): void
    {
        $this->workspaces = $this->nodeDataRepository->collectWorkspaceAndAllBaseWorkspaces($this->workspace);
    }

    protected function addDimensionJoinConstraints(QueryBuilder $queryBuilder): void
    {
        $this->nodeDataRepository->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $this->dimensions);
    }

    /**
     * Create Doctrine query builder with workspace and dimension filters already applied.
     *
     * @return QueryBuilder
     */
    protected function makeQueryBuilder(): QueryBuilder
    {
        $workspaceNames = [];
        foreach ($this->workspaces as $workspace) {
            $workspaceNames[] = $workspace->getName();
        }
        $queryBuilder = $this->nodeDataRepository->createQueryBuilder($workspaceNames);
        $this->addDimensionJoinConstraints($queryBuilder);
        return $queryBuilder;
    }

    /**
     * @param NodeData[] $nodes
     * @return NodeData[]
     */
    protected function withoutRemovedNodes(array $nodes): array
    {
        return $this->nodeDataRepository->withoutRemovedNodes($nodes);
    }

    /**
     * @param NodeData[] $nodes
     * @return NodeData[]
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    protected function reduceNodeVariantsByWorkspacesAndDimensions(array $nodes): array
    {
        return $this->nodeDataRepository->reduceNodeVariantsByWorkspacesAndDimensions($nodes, $this->workspaces, $this->dimensions);
    }

    /**
     * @param NodeDataFilterInterface $filter
     * @return NodeDataQuery
     */
    public function filter(NodeDataFilterInterface $filter): NodeDataQuery
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * @param NodeDataOrderInterface $order
     * @return NodeDataQuery
     */
    public function order(NodeDataOrderInterface $order): NodeDataQuery
    {
        $this->orders[] = $order;
        return $this;
    }

    /**
     * Applies filters and orders to Doctrine query builder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function apply(QueryBuilder $queryBuilder): void
    {
        $this->applyFilter($queryBuilder);
        $this->applyOrder($queryBuilder);
    }

    /**
     * Applies filters to Doctrine query builder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function applyFilter(QueryBuilder $queryBuilder): void
    {
        foreach ($this->filters as $filter) {
            $filter->applyFilter($queryBuilder);
        }
    }

    /**
     * Applies orders to Doctrine query builder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function applyOrder(QueryBuilder $queryBuilder): void
    {
        foreach ($this->orders as $order) {
            $order->applyOrder($queryBuilder);
        }
    }

    /**
     * Execute query and return nodes.
     *
     * @param int $limit
     * @return array
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function get(?int $limit = null): array
    {
        $queryBuilder = $this->makeQueryBuilder();
        $this->apply($queryBuilder);
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }
        $result = $queryBuilder->getQuery()->getResult();
        $result = $this->reduceNodeVariantsByWorkspacesAndDimensions($result);
        $result = $this->withoutRemovedNodes($result);
        return $result;
    }

    /**
     * Count number of nodes a query would return.
     *
     * @param int|null $limit
     * @return int
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function count(?int $limit = null): int
    {
        // Until we can do the filter process completely in SQL we must do the counting locally.
        $result = $this->get($limit);
        return count($result);
    }
}
