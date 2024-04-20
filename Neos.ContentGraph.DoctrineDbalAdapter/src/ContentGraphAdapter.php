<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\MaybeVersion;

/**
 * DBAL implementation of low level read query operations for the content graph
 *
 * @internal
 */
class ContentGraphAdapter implements ContentGraphAdapterInterface
{
    private readonly NodeQueryBuilder $nodeQueryBuilder;

    public function __construct(
        private readonly Connection $dbalConnection,
        string $tableNamePrefix,
        public readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeFactory $nodeFactory,
        public ?WorkspaceName $workspaceName,
        public ?ContentStreamId $contentStreamId,
    ) {
        if ($this->workspaceName === null && $this->contentStreamId === null) {
            throw new \InvalidArgumentException('Neither ContentStreamId nor WorkspaceName given in creation of ContentGraphAdapter, one is required.', 1712746528);
        }

        $this->nodeQueryBuilder = new NodeQueryBuilder($dbalConnection, $tableNamePrefix);
    }

    public function rootNodeAggregateWithTypeExists(NodeTypeName $nodeTypeName): bool
    {
        try {
            return (bool)$this->findRootNodeAggregateByType($nodeTypeName);
        } catch (\Exception $_) {
        }

        return false;
    }

    public function findRootNodeAggregateByType(NodeTypeName $nodeTypeName): ?NodeAggregate
    {
        $rootNodeAggregateQueryBuilder = $this->nodeQueryBuilder->buildFindRootNodeAggregatesQuery($this->getContentStreamId(), FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName));
        $rootNodeAggregates = NodeAggregates::fromArray(iterator_to_array($this->mapQueryBuilderToNodeAggregates($rootNodeAggregateQueryBuilder)));
        if ($rootNodeAggregates->count() < 1) {
            throw RootNodeAggregateDoesNotExist::butWasExpectedTo($nodeTypeName);
        }
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

        return $rootNodeAggregates->first();
    }

    public function findParentNodeAggregates(NodeAggregateId $childNodeAggregateId): iterable
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery()
            ->innerJoin('n', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'ch', 'ch.parentnodeanchor = n.relationanchorpoint')
            ->innerJoin('ch', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->andWhere('cn.nodeaggregateid = :nodeAggregateId')
            ->setParameters([
                'nodeAggregateId' => $childNodeAggregateId->value,
                'contentStreamId' => $this->getContentStreamId()->value
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findNodeAggregateById(NodeAggregateId $nodeAggregateId): ?NodeAggregate
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeAggregateQuery()
            ->andWhere('n.nodeaggregateid = :nodeAggregateId')
            ->orderBy('n.relationanchorpoint', 'DESC')
            ->setParameters([
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $this->getContentStreamId()->value
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->getContentStreamId(),
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(NodeAggregateId $childNodeAggregateId, OriginDimensionSpacePoint $childOriginDimensionSpacePoint): ?NodeAggregate
    {
        $subQueryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('pn.nodeaggregateid')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->node(), 'pn')
            ->innerJoin('pn', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('ch.contentstreamid = :contentStreamId')
            ->andWhere('ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash')
            ->andWhere('cn.nodeaggregateid = :childNodeAggregateId')
            ->andWhere('cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash');

        $queryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->node(), 'n')
            ->innerJoin('n', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->nodeQueryBuilder->contentGraphTableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = (' . $subQueryBuilder->getSQL() . ')')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->setParameters([
                'contentStreamId' => $this->getContentStreamId()->value,
                'childNodeAggregateId' => $childNodeAggregateId->value,
                'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
            ]);

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $this->fetchRows($queryBuilder),
            $this->getContentStreamId(),
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): iterable
    {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->getContentStreamId());

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): iterable
    {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->getContentStreamId())
            ->andWhere('cn.classification = :tetheredClassification')
            ->setParameter('tetheredClassification', NodeAggregateClassification::CLASSIFICATION_TETHERED->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('dsp.dimensionspacepoint, h.dimensionspacepointhash')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'h')
            ->innerJoin('h', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'n', 'n.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('h', $this->nodeQueryBuilder->contentGraphTableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->innerJoin('n', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'ph', 'ph.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('h.contentstreamid = :contentStreamId')
            ->andWhere('h.dimensionspacepointhash IN (:dimensionSpacePointHashes)')
            ->andWhere('h.name = :nodeName')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
                'contentStreamId' => $this->getContentStreamId()->value,
                'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
                'nodeName' => $nodeName->value
            ], [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
            ]);
        $dimensionSpacePoints = [];
        foreach ($this->fetchRows($queryBuilder) as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = DimensionSpacePoint::fromJsonString($hierarchyRelationData['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function findChildNodeAggregatesByName(NodeAggregateId $parentNodeAggregateId, NodeName $name): iterable
    {
        $queryBuilder = $this->nodeQueryBuilder->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->getContentStreamId())
            ->andWhere('ch.name = :relationName')
            ->setParameter('relationName', $name->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function subgraphContainsNodes(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeQuery($this->getContentStreamId(), $dimensionSpacePoint, 'n', 'COUNT(*)');
        try {
            $result = $this->executeQuery($queryBuilder)->fetchOne();
            if (!is_int($result)) {
                throw new \RuntimeException(sprintf('Expected result to be of type integer but got: %s', get_debug_type($result)), 1678366902);
            }

            return $result > 0;
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to count all nodes: %s', $e->getMessage()), 1678364741, $e);
        }
    }

    public function findNodeInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $nodeAggregateId): ?Node
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeByIdQuery($nodeAggregateId, $this->getContentStreamId(), $coveredDimensionSpacePoint);

        // TODO: Do we need subtree tag support here, not for visibility at least
        return $this->fetchNode($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findChildNodesInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId): Nodes
    {
        $queryBuilder = $this->buildChildNodesQuery($parentNodeAggregateId, $coveredDimensionSpacePoint);
        $queryBuilder->addOrderBy('h.position');

        return $this->fetchNodes($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findParentNodeInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $childNodeAggregateId): ?Node
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicParentNodeQuery($childNodeAggregateId, $this->getContentStreamId(), $coveredDimensionSpacePoint);
        return $this->fetchNode($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findChildNodeByNameInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, NodeName $nodeName): ?Node
    {
        $startingNode = $this->findNodeInSubgraph($coveredDimensionSpacePoint, $parentNodeAggregateId);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode(NodePath::fromNodeNames($nodeName), $startingNode, $coveredDimensionSpacePoint)
            : null;
    }

    public function findPreceedingSiblingNodesInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $startingSiblingNodeAggregateId): Nodes
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeSiblingsQuery(true, $startingSiblingNodeAggregateId, $this->getContentStreamId(), $coveredDimensionSpacePoint);

        return $this->fetchNodes($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findSucceedingSiblingNodesInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $startingSiblingNodeAggregateId): Nodes
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicNodeSiblingsQuery(false, $startingSiblingNodeAggregateId, $this->getContentStreamId(), $coveredDimensionSpacePoint);

        return $this->fetchNodes($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function hasContentStream(): bool
    {
        try {
            /* @var $state string|false */
            $state = $this->dbalConnection->executeQuery(
                'SELECT state FROM cr_default_p_contentstream WHERE contentStreamId = :contentStreamId',
                [
                    'contentStreamId' => $this->getContentStreamId()->value,
                ]
            )->fetchOne();

            return $state !== false;
        } catch (ContentStreamDoesNotExistYet $_) {
            return false;
        }
    }

    public function findStateForContentStream(): ?ContentStreamState
    {
        /* @var $state string|false */
        $state = $this->dbalConnection->executeQuery(
            'SELECT state FROM cr_default_p_contentstream WHERE contentStreamId = :contentStreamId',
            [
                'contentStreamId' => $this->getContentStreamId()->value,
            ]
        )->fetchOne();

        return ContentStreamState::tryFrom($state ?: '');
    }

    public function findVersionForContentStream(): MaybeVersion
    {
        /* @var $version int|false */
        $version = $this->dbalConnection->executeQuery(
            'SELECT version FROM cr_default_p_contentstream WHERE contentStreamId = :contentStreamId',
            [
                'contentStreamId' => $this->getContentStreamId()->value,
            ]
        )->fetchOne();

        $versionObject = $version !== false ? Version::fromInteger($version) : null;
        return MaybeVersion::fromVersionOrNull($versionObject);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return iterable<NodeAggregate>
     */
    private function mapQueryBuilderToNodeAggregates(QueryBuilder $queryBuilder): iterable
    {
        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $this->fetchRows($queryBuilder),
            $this->getContentStreamId(),
            VisibilityConstraints::withoutRestrictions()
        );
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->dbalConnection->createQueryBuilder();
    }

    private function buildChildNodesQuery(NodeAggregateId $parentNodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): QueryBuilder
    {
        $queryBuilder = $this->nodeQueryBuilder->buildBasicChildNodesQuery($parentNodeAggregateId, $this->getContentStreamId(), $dimensionSpacePoint);

        return $queryBuilder;
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

    /**
     * @param QueryBuilder $queryBuilder
     * @return \Doctrine\DBAL\ForwardCompatibility\Result<mixed>
     * @throws DbalException
     */
    private function executeQuery(QueryBuilder $queryBuilder): Result
    {
        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            throw new \RuntimeException(sprintf('Expected instance of %s, got %s', Result::class, get_debug_type($result)), 1678370012);
        }

        return $result;
    }

    private function findNodeByPathFromStartingNode(NodePath $path, Node $startingNode, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        $currentNode = $startingNode;

        foreach ($path->getParts() as $edgeName) {
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->nodeAggregateId, $edgeName, $dimensionSpacePoint);
            if ($currentNode === null) {
                return null;
            }
        }

        return $currentNode;
    }

    /**
     * Find a single child node by its name
     *
     * @return Node|null the node that is connected to its parent with the specified $nodeName, or NULL if no matching node exists or the parent node is not accessible
     */
    private function findChildNodeConnectedThroughEdgeName(NodeAggregateId $parentNodeAggregateId, NodeName $nodeName, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('cn.*, h.name, h.subtreetags')
            ->from($this->nodeQueryBuilder->contentGraphTableNames->node(), 'pn')
            ->innerJoin('pn', $this->nodeQueryBuilder->contentGraphTableNames->hierachyRelation(), 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->nodeQueryBuilder->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = h.childnodeanchor')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash)
            ->andWhere('h.name = :edgeName')->setParameter('edgeName', $nodeName->value);

        return $this->fetchNode($queryBuilder, $dimensionSpacePoint);
    }

    private function fetchNode(QueryBuilder $queryBuilder, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        try {
            $nodeRow = $this->executeQuery($queryBuilder)->fetchAssociative();
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch node: %s', $e->getMessage()), 1678286030, $e);
        }
        if ($nodeRow === false) {
            return null;
        }

        return $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->getContentStreamId(),
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    private function fetchNodes(QueryBuilder $queryBuilder, DimensionSpacePoint $dimensionSpacePoint): Nodes
    {
        try {
            $nodeRows = $this->executeQuery($queryBuilder)->fetchAllAssociative();
        } catch (DbalDriverException | DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch nodes: %s', $e->getMessage()), 1678292896, $e);
        }

        return $this->nodeFactory->mapNodeRowsToNodes($nodeRows, $this->getContentStreamId(), $dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
    }

    public function getWorkspaceName(): WorkspaceName
    {
        if ($this->workspaceName !== null) {
            return $this->workspaceName;
        }

        // TODO: This table is not allowed here...
        $query = $this->dbalConnection->prepare('SELECT workspacename FROM cr_default_p_workspace WHERE currentcontentstreamid LIKE :contentStreamId');
        $result = $query->executeQuery(['contentStreamId' => $this->getContentStreamId()->value]);
        $workspaceNameString = $result->fetchOne();
        if (!$workspaceNameString) {
            throw new WorkspaceDoesNotExist(sprintf('A workspace for the ContentStreamId "%s" was not found, cannot proceed.', $this->getContentStreamId()->value), 1712746408);
        }

        $this->workspaceName = WorkspaceName::fromString($workspaceNameString);

        return $this->workspaceName;
    }

    public function getContentStreamId(): ContentStreamId
    {
        if ($this->contentStreamId !== null) {
            return $this->contentStreamId;
        }

        // TODO: This table is not allowed here...
        $query = $this->dbalConnection->prepare('SELECT currentcontentstreamid FROM cr_default_p_workspace WHERE workspacename LIKE :workspaceName');
        $result = $query->executeQuery(['workspaceName' => $this->getWorkspaceName()->value]);
        $contentStreamIdString = $result->fetchOne();
        if (!$contentStreamIdString) {
            throw new ContentStreamDoesNotExistYet(sprintf('A ContentStream for the WorkspaceName "%s" was not found, cannot proceed.', $this->workspaceName?->value), 1712750421);
        }

        $this->contentStreamId = ContentStreamId::fromString($contentStreamIdString);

        return $this->contentStreamId;
    }

    public function getWorkspace(): Workspace
    {
        $query = $this->dbalConnection->prepare('SELECT * FROM cr_default_p_workspace WHERE workspacename LIKE :workspaceName');
        $result = $query->executeQuery(['workspaceName' => $this->getWorkspaceName()->value]);
        $row = $result->fetchAssociative();

        // We can assume that we get a row otherwise getWorkspaceName would have thrown already

        return new Workspace(
            /** @phpstan-ignore-next-line */
            WorkspaceName::fromString($row['workspacename']),
            !empty($row['baseworkspacename']) ? WorkspaceName::fromString($row['baseworkspacename']) : null,
            /** @phpstan-ignore-next-line */
            WorkspaceTitle::fromString($row['workspacetitle']),
            /** @phpstan-ignore-next-line */
            WorkspaceDescription::fromString($row['workspacedescription']),
            /** @phpstan-ignore-next-line */
            ContentStreamId::fromString($row['currentcontentstreamid']),
            /** @phpstan-ignore-next-line */
            WorkspaceStatus::from($row['status']),
            /** @phpstan-ignore-next-line */
            $row['workspaceowner']
        );
    }
}
