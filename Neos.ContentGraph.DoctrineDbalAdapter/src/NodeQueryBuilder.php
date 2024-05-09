<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
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
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal Implementation detail of the DoctrineDbalAdapter
 */
final readonly class NodeQueryBuilder
{
    public function __construct(
        private Connection $connection,
        public ContentGraphTableNames $contentGraphTableNames
    ) {
    }

    public function buildBasicNodeAggregateQuery(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('n.*, h.contentstreamid, h.name, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->contentGraphTableNames->node(), 'n')
            ->innerJoin('n', $this->contentGraphTableNames->hierachyRelation(), 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->contentGraphTableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash')
            ->where('h.contentstreamid = :contentStreamId');

        return $queryBuilder;
    }

    public function buildChildNodeAggregateQuery(NodeAggregateId $parentNodeAggregateId, ContentStreamId $contentStreamId): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('cn.*, ch.name, ch.contentstreamid, ch.subtreetags, cdsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->contentGraphTableNames->node(), 'pn')
            ->innerJoin('pn', $this->contentGraphTableNames->hierachyRelation(), 'ph', 'ph.childnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->contentGraphTableNames->hierachyRelation(), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->contentGraphTableNames->dimensionSpacePoints(), 'cdsp', 'cdsp.hash = ch.dimensionspacepointhash')
            ->innerJoin('ch', $this->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')
            ->andWhere('ph.contentstreamid = :contentStreamId')
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->orderBy('ch.position')
            ->setParameters([
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
            ]);
    }

    public function buildFindRootNodeAggregatesQuery(ContentStreamId $contentStreamId, FindRootNodeAggregatesFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->buildBasicNodeAggregateQuery()
            ->andWhere('h.parentnodeanchor = :rootEdgeParentAnchorId')
            ->setParameters([
                'contentStreamId' => $contentStreamId->value,
                'rootEdgeParentAnchorId' => NodeRelationAnchorPoint::forRootEdge()->value,
            ]);

        if ($filter->nodeTypeName !== null) {
            $queryBuilder->andWhere('n.nodetypename = :nodeTypeName')->setParameter('nodeTypeName', $filter->nodeTypeName->value);
        }

        return $queryBuilder;
    }

    public function buildBasicNodeQuery(ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint, string $nodeTableAlias = 'n', string $select = 'n.*, h.name, h.subtreetags'): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select($select)
            ->from($this->contentGraphTableNames->node(), $nodeTableAlias)
            ->innerJoin($nodeTableAlias, $this->contentGraphTableNames->hierachyRelation(), 'h', 'h.childnodeanchor = ' . $nodeTableAlias . '.relationanchorpoint')
            ->where('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash);
    }

    public function buildBasicChildNodesQuery(NodeAggregateId $parentNodeAggregateId, ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('n.*, h.name, h.subtreetags')
            ->from($this->contentGraphTableNames->node(), 'pn')
            ->innerJoin('pn', $this->contentGraphTableNames->hierachyRelation(), 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->contentGraphTableNames->node(), 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->where('pn.nodeaggregateid = :parentNodeAggregateId')->setParameter('parentNodeAggregateId', $parentNodeAggregateId->value)
            ->andWhere('h.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $contentStreamId->value)
            ->andWhere('h.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash);
    }

    public function buildBasicParentNodeQuery(NodeAggregateId $childNodeAggregateId, ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('pn.*, ch.name, ch.subtreetags')
            ->from($this->contentGraphTableNames->node(), 'pn')
            ->innerJoin('pn', $this->contentGraphTableNames->hierachyRelation(), 'ph', 'ph.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->contentGraphTableNames->node(), 'cn', 'cn.relationanchorpoint = ph.childnodeanchor')
            ->innerJoin('pn', $this->contentGraphTableNames->hierachyRelation(), 'ch', 'ch.childnodeanchor = pn.relationanchorpoint')
            ->where('cn.nodeaggregateid = :childNodeAggregateId')->setParameter('childNodeAggregateId', $childNodeAggregateId->value)
            ->andWhere('ph.contentstreamid = :contentStreamId')->setParameter('contentStreamId', $contentStreamId->value)
            ->andWhere('ch.contentstreamid = :contentStreamId')
            ->andWhere('ph.dimensionspacepointhash = :dimensionSpacePointHash')->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash)
            ->andWhere('ch.dimensionspacepointhash = :dimensionSpacePointHash');
    }

    public function buildBasicNodeSiblingsQuery(bool $preceding, NodeAggregateId $siblingNodeAggregateId, ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): QueryBuilder
    {
        $sharedSubQuery = $this->createQueryBuilder()
            ->from($this->contentGraphTableNames->hierachyRelation(), 'sh')
            ->innerJoin('sh', $this->contentGraphTableNames->node(), 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->where('sn.nodeaggregateid = :siblingNodeAggregateId')
            ->andWhere('sh.contentstreamid = :contentStreamId')
            ->andWhere('sh.dimensionspacepointhash = :dimensionSpacePointHash');

        $parentNodeAnchorSubQuery = (clone $sharedSubQuery)->select('sh.parentnodeanchor');
        $siblingPositionSubQuery = (clone $sharedSubQuery)->select('sh.position');

        return $this->buildBasicNodeQuery($contentStreamId, $dimensionSpacePoint)
            ->andWhere('h.parentnodeanchor = (' . $parentNodeAnchorSubQuery->getSQL() . ')')
            ->andWhere('n.nodeaggregateid != :siblingNodeAggregateId')->setParameter('siblingNodeAggregateId', $siblingNodeAggregateId->value)
            ->andWhere('h.position ' . ($preceding ? '<' : '>') . ' (' . $siblingPositionSubQuery->getSQL() . ')')
            ->orderBy('h.position', $preceding ? 'DESC' : 'ASC');
    }

    public function buildBasicNodesCteQuery(NodeAggregateId $entryNodeAggregateId, ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint, string $cteName = 'ancestry', string $cteAlias = 'pn'): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('*')
            ->from($cteName, $cteAlias)
            ->setParameter('contentStreamId', $contentStreamId->value)
            ->setParameter('dimensionSpacePointHash', $dimensionSpacePoint->hash)
            ->setParameter('entryNodeAggregateId', $entryNodeAggregateId->value);
    }

    public function addNodeTypeCriteria(QueryBuilder $queryBuilder, ExpandedNodeTypeCriteria $constraintsWithSubNodeTypes, string $nodeTableAlias = 'n'): void
    {
        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
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

    public function addSearchTermConstraints(QueryBuilder $queryBuilder, SearchTerm $searchTerm, string $nodeTableAlias = 'n'): void
    {
        $queryBuilder->andWhere('JSON_SEARCH(' . $nodeTableAlias . '.properties, "one", :searchTermPattern, NULL, "$.*.value") IS NOT NULL')->setParameter('searchTermPattern', '%' . $searchTerm->term . '%');
    }

    public function addPropertyValueConstraints(QueryBuilder $queryBuilder, PropertyValueCriteriaInterface $propertyValue, string $nodeTableAlias = 'n'): void
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

    public function extractPropertyValue(PropertyName $propertyName, string $nodeTableAlias): string
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

    /**
     * @return QueryBuilder
     * @throws DBALException
     */
    public function buildfindUsedNodeTypeNamesQuery(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('DISTINCT nodetypename')
            ->from($this->contentGraphTableNames->node());
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }

    private function createUniqueParameterName(): string
    {
        return 'param_' . str_replace('-', '', UuidFactory::create());
    }
}
