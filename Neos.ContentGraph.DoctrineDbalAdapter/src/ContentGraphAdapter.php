<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
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
 * @įnternal
 */
class ContentGraphAdapter implements ContentGraphAdapterInterface
{
    public function __construct(
        private readonly Connection $dbalConnection,
        private readonly string $tableNamePrefix,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        public ?WorkspaceName $workspaceName,
        public ?ContentStreamId $contentStreamId,
    ) {
        if ($this->workspaceName === null && $this->contentStreamId === null) {
            throw new \InvalidArgumentException('Neither ContentStreamId nor WorkspaceName given in creation of ContentGraphAdapter, one is required.', 1712746528);
        }
    }

    public function rootNodeAggregateWithTypeExists(NodeTypeName $nodeTypeName): bool
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('COUNT(n.relationanchorpoint)')
            ->from($this->getTablenameForNode(), 'n')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->getTablenameForDimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('h.contentstreamid = :contentStreamId')
            ->andWhere('h.parentnodeanchor = :rootEdgeParentAnchorId')
            ->setParameters([
                'contentStreamId' => $this->getContentStreamId()->value,
                'rootEdgeParentAnchorId' => NodeRelationAnchorPoint::forRootEdge()->value,
            ]);

        $queryBuilder
            ->andWhere('n.nodetypename = :nodeTypeName')
            ->setParameter('nodeTypeName', $nodeTypeName->value);

        $result = $queryBuilder->execute();
        if (!$result instanceof Result) {
            return false;
        }

        return $result->fetchOne() > 0;
    }

    public function findParentNodeAggregates(NodeAggregateId $childNodeAggregateId): iterable
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('pn.*, ph.name, ph.contentstreamid, ph.subtreetags, pdsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->getTablenameForNode(), 'pn')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ph', 'ph.childnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->getTablenameForNode(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->innerJoin('ph', $this->getTablenameForDimensionSpacePoints(), 'pdsp', 'pdsp.hash = ph.dimensionspacepointhash')
            ->where('cn.nodeaggregateid = :nodeAggregateId')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->setParameters([
                'nodeAggregateId' => $childNodeAggregateId->value,
                'contentStreamId' => $this->getContentStreamId()->value
            ]);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findNodeAggregateById(NodeAggregateId $nodeAggregateId): ?NodeAggregate
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->getTablenameForHierachyRelation(), 'h')
            ->innerJoin('h', $this->getTablenameForNode(), 'n', 'n.relationanchorpoint = h.childnodeanchor')
            ->innerJoin('h', $this->getTablenameForDimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('n.nodeaggregateid = :nodeAggregateId')
            ->andWhere('h.contentstreamid = :contentStreamId')
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
            ->from($this->getTablenameForNode(), 'pn')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->getTablenameForNode(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('ch.contentstreamid = :contentStreamId')
            ->andWhere('ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash')
            ->andWhere('cn.nodeaggregateid = :childNodeAggregateId')
            ->andWhere('cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash');

        $queryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('n.*, h.name, h.contentstreamid, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->getTablenameForNode(), 'n')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->getTablenameForDimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
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
        $queryBuilder = $this->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->getContentStreamId());

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): iterable
    {
        $queryBuilder = $this->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->getContentStreamId())
            ->andWhere('cn.classification = :tetheredClassification')
            ->setParameter('tetheredClassification', NodeAggregateClassification::CLASSIFICATION_TETHERED->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('dsp.dimensionspacepoint, h.dimensionspacepointhash')
            ->from($this->getTablenameForHierachyRelation(), 'h')
            ->innerJoin('h', $this->getTablenameForNode(), 'n', 'n.relationanchorpoint = h.parentnodeanchor')
            ->innerJoin('h', $this->getTablenameForDimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'ph', 'ph.childnodeanchor = n.relationanchorpoint')
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
        $queryBuilder = $this->buildChildNodeAggregateQuery($parentNodeAggregateId, $this->getContentStreamId())
            ->andWhere('ch.name = :relationName')
            ->setParameter('relationName', $name->value);

        return $this->mapQueryBuilderToNodeAggregates($queryBuilder);
    }

    public function subgraphContainsNodes(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->getTablenameForNode(), 'n')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash);
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
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.subtreetags')
            ->from($this->getTablenameForNode(), 'n')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :nodeAggregateId')->setParameter('nodeAggregateId', $nodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $coveredDimensionSpacePoint->hash);

        // TODO: Do we need subtree tag support here, not for visibility at least
        return $this->fetchNode($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findChildNodesInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId): Nodes
    {
        $filter = FindChildNodesFilter::create();
        $queryBuilder = $this->buildChildNodesQuery($parentNodeAggregateId, $coveredDimensionSpacePoint, $filter);
        $queryBuilder->addOrderBy('h.position');

        return $this->fetchNodes($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findParentNodeInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $childNodeAggregateId): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('pn.*, ch.name, ch.subtreetags')
            ->from($this->getTablenameForNode(), 'pn')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ph', 'ph.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->getTablenameForNode(), 'cn', 'cn.relationanchorpoint = ph.childnodeanchor')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ch', 'ch.childnodeanchor = pn.relationanchorpoint')
            ->where('cn.nodeaggregateid = :childNodeAggregateId')->setParameter('childNodeAggregateId', $childNodeAggregateId->value)
            ->andWhere('ph.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $coveredDimensionSpacePoint->hash)
            ->andWhere('ch.dimensionspacepointhash = :dimensionSpacePointHash');

        return $this->fetchNode($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findChildNodeByNameInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, NodeName $nodeName): ?Node
    {
        $startingNode = $this->findNodeById($parentNodeAggregateId, $coveredDimensionSpacePoint);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode(NodePath::fromNodeNames($nodeName), $startingNode, $coveredDimensionSpacePoint)
            : null;
    }

    public function findPreceedingSiblingNodesInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $startingSiblingNodeAggregateId): Nodes
    {
        $queryBuilder = $this->buildSiblingsQuery(true, $startingSiblingNodeAggregateId, $coveredDimensionSpacePoint, FindPrecedingSiblingNodesFilter::create());

        return $this->fetchNodes($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function findSucceedingSiblingNodesInSubgraph(DimensionSpacePoint $coveredDimensionSpacePoint, NodeAggregateId $startingSiblingNodeAggregateId): Nodes
    {
        $queryBuilder = $this->buildSiblingsQuery(false, $startingSiblingNodeAggregateId, $coveredDimensionSpacePoint, FindSucceedingSiblingNodesFilter::create());

        return $this->fetchNodes($queryBuilder, $coveredDimensionSpacePoint);
    }

    public function hasContentStream(): bool
    {
        try {
            $this->getContentStreamId();
        } catch (ContentStreamDoesNotExistYet $_) {
            return false;
        }

        return true;
    }

    public function findStateForContentStream(): ?ContentStreamState
    {
        /* @var $state string|false */
        $state = $this->dbalConnection->executeQuery(
            '
            SELECT state FROM cr_default_p_contentstream
                WHERE contentStreamId = :contentStreamId
            ',
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
            '
            SELECT version FROM cr_default_p_contentstream
                WHERE contentStreamId = :contentStreamId
            ',
            [
                'contentStreamId' => $this->getContentStreamId()->value,
            ]
        )->fetchOne();

        $versionObject = $version !== false ? Version::fromInteger($version) : null;
        return MaybeVersion::fromVersionOrNull($versionObject);
    }

    private function getTablenameForNode(): string
    {
        return $this->tableNamePrefix . '_node';
    }

    private function getTablenameForHierachyRelation(): string
    {
        return $this->tableNamePrefix . '_hierarchyrelation';
    }

    private function getTablenameForDimensionSpacePoints(): string
    {
        return $this->tableNamePrefix . '_dimensionspacepoints';
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

    private function buildChildNodeAggregateQuery(NodeAggregateId $parentNodeAggregateId, ContentStreamId $contentStreamId): QueryBuilder
    {
        return $this->dbalConnection->createQueryBuilder()
            ->select('cn.*, ch.name, ch.contentstreamid, ch.subtreetags, cdsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->getTablenameForNode(), 'pn')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ph', 'ph.childnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->getTablenameForDimensionSpacePoints(), 'cdsp', 'cdsp.hash = ch.dimensionspacepointhash')
            ->innerJoin('ch', $this->getTablenameForNode(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->orderBy('ch.position')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
            ]);
    }

    private function buildChildNodesQuery(NodeAggregateId $parentNodeAggregateId, DimensionSpacePoint $dimensionSpacePoint, FindChildNodesFilter|CountChildNodesFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.subtreetags')
            ->from($this->getTablenameForNode(), 'pn')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->getTablenameForNode(), 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash);
        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilder, $filter->nodeTypes);
        }
        if ($filter->searchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }

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

    public function findNodeById(NodeAggregateId $nodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): ?Node
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.subtreetags')
            ->from($this->getTablenameForNode(), 'n')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('n.nodeaggregateid = :nodeAggregateId')->setParameter('nodeAggregateId', $nodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash);

        return $this->fetchNode($queryBuilder, $dimensionSpacePoint);
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
            ->from($this->getTablenameForNode(), 'pn')
            ->innerJoin('pn', $this->getTablenameForHierachyRelation(), 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->getTablenameForNode(), 'cn', 'cn.relationanchorpoint = h.childnodeanchor')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash)
            ->andWhere('h.name = :edgeName')->setParameter('edgeName', $nodeName->value);

        return $this->fetchNode($queryBuilder, $dimensionSpacePoint);
    }

    private function buildSiblingsQuery(bool $preceding, NodeAggregateId $siblingNodeAggregateId, DimensionSpacePoint $dimensionSpacePoint, FindPrecedingSiblingNodesFilter|FindSucceedingSiblingNodesFilter $filter): QueryBuilder
    {
        $parentNodeAnchorSubQuery = $this->createQueryBuilder()
            ->select('sh.parentnodeanchor')
            ->from($this->getTablenameForHierachyRelation(), 'sh')
            ->innerJoin('sh', $this->getTablenameForNode(), 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->where('sn.nodeaggregateid = :siblingNodeAggregateId')
            ->andWhere('sh.contentstreamid = :contentStreamId')
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash');

        $siblingPositionSubQuery = $this->createQueryBuilder()
            ->select('sh.position')
            ->from($this->getTablenameForHierachyRelation(), 'sh')
            ->innerJoin('sh', $this->getTablenameForNode(), 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->where('sn.nodeaggregateid = :siblingNodeAggregateId')
            ->andWhere('sh.contentstreamid = :contentStreamId')
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash');

        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.name, h.subtreetags')
            ->from($this->getTablenameForNode(), 'n')
            ->innerJoin('n', $this->getTablenameForHierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $this->getContentStreamId()->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash)
            ->andWhere('h.parentnodeanchor = (' . $parentNodeAnchorSubQuery->getSQL() . ')')
            ->andWhere('n.nodeaggregateid != :siblingNodeAggregateId')->setParameter('siblingNodeAggregateId', $siblingNodeAggregateId->value)
            ->andWhere('h.position ' . ($preceding ? '<' : '>') . ' (' . $siblingPositionSubQuery->getSQL() . ')')
            ->orderBy('h.position', $preceding ? 'DESC' : 'ASC');

        if ($filter->nodeTypes !== null) {
            $this->addNodeTypeCriteria($queryBuilder, $filter->nodeTypes);
        }
        if ($filter->searchTerm !== null) {
            $this->addSearchTermConstraints($queryBuilder, $filter->searchTerm);
        }
        if ($filter->propertyValue !== null) {
            $this->addPropertyValueConstraints($queryBuilder, $filter->propertyValue);
        }
        if ($filter->pagination !== null) {
            $this->applyPagination($queryBuilder, $filter->pagination);
        }

        return $queryBuilder;
    }

    private function addNodeTypeCriteria(QueryBuilder $queryBuilder, NodeTypeCriteria $nodeTypeCriteria, string $nodeTableAlias = 'n'): void
    {
        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
        $constraintsWithSubNodeTypes = ExpandedNodeTypeCriteria::create($nodeTypeCriteria, $this->nodeTypeManager);
        $allowanceQueryPart = '';
        if (!$constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->isEmpty()) {
            $allowanceQueryPart = $queryBuilder->expr()->in($nodeTablePrefix . 'nodetypename', ':explicitlyAllowedNodeTypeNames');
            $queryBuilder->setParameter('explicitlyAllowedNodeTypeNames', $constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->toStringArray(), Connection::PARAM_STR_ARRAY);
        }
        $denyQueryPart = '';
        if (!$constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $denyQueryPart = $queryBuilder->expr()->notIn($nodeTablePrefix . 'nodetypename', ':explicitlyDisallowedNodeTypeNames');
            $queryBuilder->setParameter('explicitlyDisallowedNodeTypeNames', $constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->toStringArray(), Connection::PARAM_STR_ARRAY);
        }
        if ($allowanceQueryPart && $denyQueryPart) {
            if ($constraintsWithSubNodeTypes->isWildCardAllowed) {
                $queryBuilder->andWhere($queryBuilder->expr()->or($allowanceQueryPart, $denyQueryPart));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->and($allowanceQueryPart, $denyQueryPart));
            }
        } elseif ($allowanceQueryPart && !$constraintsWithSubNodeTypes->isWildCardAllowed) {
            $queryBuilder->andWhere($allowanceQueryPart);
        } elseif ($denyQueryPart) {
            $queryBuilder->andWhere($denyQueryPart);
        }
    }

    private function addSearchTermConstraints(QueryBuilder $queryBuilder, SearchTerm $searchTerm, string $nodeTableAlias = 'n'): void
    {
        $queryBuilder->andWhere('JSON_SEARCH(' . $nodeTableAlias . '.properties, "one", :searchTermPattern, NULL, "$.*.value") IS NOT NULL')->setParameter('searchTermPattern', '%' . $searchTerm->term . '%');
    }

    private function addPropertyValueConstraints(QueryBuilder $queryBuilder, PropertyValueCriteriaInterface $propertyValue, string $nodeTableAlias = 'n'): void
    {
        $queryBuilder->andWhere($this->propertyValueConstraints($queryBuilder, $propertyValue, $nodeTableAlias));
    }

    private function propertyValueConstraints(QueryBuilder $queryBuilder, PropertyValueCriteriaInterface $propertyValue, string $nodeTableAlias): string
    {
        return match ($propertyValue::class) {
            AndCriteria::class => (string)$queryBuilder->expr()->and($this->propertyValueConstraints($queryBuilder, $propertyValue->criteria1, $nodeTableAlias), $this->propertyValueConstraints($queryBuilder, $propertyValue->criteria2, $nodeTableAlias)),
            NegateCriteria::class => 'NOT (' . $this->propertyValueConstraints($queryBuilder, $propertyValue->criteria, $nodeTableAlias) . ')',
            OrCriteria::class => (string)$queryBuilder->expr()->or($this->propertyValueConstraints($queryBuilder, $propertyValue->criteria1, $nodeTableAlias), $this->propertyValueConstraints($queryBuilder, $propertyValue->criteria2, $nodeTableAlias)),
            PropertyValueContains::class => $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, '%' . $propertyValue->value . '%', $nodeTableAlias, $propertyValue->caseSensitive),
            PropertyValueEndsWith::class => $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, '%' . $propertyValue->value, $nodeTableAlias, $propertyValue->caseSensitive),
            PropertyValueEquals::class => is_string($propertyValue->value) ? $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, $nodeTableAlias, $propertyValue->caseSensitive) : $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '=', $nodeTableAlias),
            PropertyValueGreaterThan::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '>', $nodeTableAlias),
            PropertyValueGreaterThanOrEqual::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '>=', $nodeTableAlias),
            PropertyValueLessThan::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '<', $nodeTableAlias),
            PropertyValueLessThanOrEqual::class => $this->comparePropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value, '<=', $nodeTableAlias),
            PropertyValueStartsWith::class => $this->searchPropertyValueStatement($queryBuilder, $propertyValue->propertyName, $propertyValue->value . '%', $nodeTableAlias, $propertyValue->caseSensitive),
            default => throw new \InvalidArgumentException(sprintf('Invalid/unsupported property value criteria "%s"', get_debug_type($propertyValue)), 1679561062),
        };
    }

    private function comparePropertyValueStatement(QueryBuilder $queryBuilder, PropertyName $propertyName, string|int|float|bool $value, string $operator, string $nodeTableAlias): string
    {
        $paramName = $this->createUniqueParameterName();
        $paramType = match (gettype($value)) {
            'boolean' => ParameterType::BOOLEAN,
            'integer' => ParameterType::INTEGER,
            default => ParameterType::STRING,
        };
        $queryBuilder->setParameter($paramName, $value, $paramType);

        return $this->extractPropertyValue($propertyName, $nodeTableAlias) . ' ' . $operator . ' :' . $paramName;
    }

    private function extractPropertyValue(PropertyName $propertyName, string $nodeTableAlias): string
    {
        try {
            $escapedPropertyName = addslashes(json_encode($propertyName->value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to escape property name: %s', $e->getMessage()), 1679394579, $e);
        }

        return 'JSON_EXTRACT(' . $nodeTableAlias . '.properties, \'$.' . $escapedPropertyName . '.value\')';
    }

    private function searchPropertyValueStatement(QueryBuilder $queryBuilder, PropertyName $propertyName, string|bool|int|float $value, string $nodeTableAlias, bool $caseSensitive): string
    {
        try {
            $escapedPropertyName = addslashes(json_encode($propertyName->value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to escape property name: %s', $e->getMessage()), 1679394579, $e);
        }
        if (is_bool($value)) {
            return 'JSON_SEARCH(' . $nodeTableAlias . '.properties, \'one\', \'' . ($value ? 'true' : 'false') . '\', NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
        }
        $paramName = $this->createUniqueParameterName();
        $queryBuilder->setParameter($paramName, $value);
        if ($caseSensitive) {
            return 'JSON_SEARCH(' . $nodeTableAlias . '.properties COLLATE utf8mb4_bin, \'one\', :' . $paramName . ' COLLATE utf8mb4_bin, NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
        }

        return 'JSON_SEARCH(' . $nodeTableAlias . '.properties, \'one\', :' . $paramName . ', NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
    }

    private function applyPagination(QueryBuilder $queryBuilder, Pagination $pagination): void
    {
        $queryBuilder
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset);
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

    private function createUniqueParameterName(): string
    {
        return 'param_' . UuidFactory::create();
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

    public function contentStreamExists(): bool
    {
        /* @var $state string|false */
        $state = $this->dbalConnection->executeQuery(
            '
            SELECT state FROM cr_default_p_contentstream
                WHERE contentStreamId = :contentStreamId
            ',
            [
                'contentStreamId' => $this->getContentStreamId()->value,
            ]
        )->fetchOne();

        return $state === false ? false : true;
    }

    public function getWorkspace(): Workspace
    {
        $query = $this->dbalConnection->prepare('SELECT * FROM cr_default_p_workspace WHERE workspacename LIKE :workspaceName');
        $result = $query->executeQuery(['workspaceName' => $this->getWorkspaceName()->value]);
        $row = $result->fetchAssociative();

        // We can assume that we get a row otherwise getWorkspaceName would have thrown already
        return new Workspace(
            WorkspaceName::fromString($row['workspacename']),
            !empty($row['baseworkspacename']) ? WorkspaceName::fromString($row['baseworkspacename']) : null,
            WorkspaceTitle::fromString($row['workspacetitle']),
            WorkspaceDescription::fromString($row['workspacedescription']),
            ContentStreamId::fromString($row['currentcontentstreamid']),
            WorkspaceStatus::from($row['status']),
            $row['workspaceowner']
        );
    }
}
