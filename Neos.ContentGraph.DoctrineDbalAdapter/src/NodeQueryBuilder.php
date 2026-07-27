<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTerm;
use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Dbal\DbalSchemaFactory;
use Neos\ContentRepository\Dbal\Query\QueryBuilder;

/**
 * @internal Implementation detail of the DoctrineDbalAdapter
 */
final readonly class NodeQueryBuilder
{
    public function __construct(
        private Connection $connection,
        private ContentGraphTableNames $tableNames
    ) {
    }

    public function buildBasicNodeAggregateQuery(HierarchyRelationSubquery $hierarchyRelationQuery): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('n.*, h.subtreetags, dsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->tableNames->node(), 'n')
            ->innerJoinTableSubquery('n', $hierarchyRelationQuery, 'h', 'h.childnodeanchor = n.relationanchorpoint')
            ->innerJoin('h', $this->tableNames->dimensionSpacePoints(), 'dsp', 'dsp.hash = h.dimensionspacepointhash');
    }

    public function buildChildNodeAggregateQuery(HierarchyRelationSubquery $hierarchyRelationQuery, NodeAggregateId $parentNodeAggregateId): QueryBuilder
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($parentNodeAggregateId);

        return $this->createQueryBuilder()
            ->select('cn.*, ch.subtreetags, cdsp.dimensionspacepoint AS covereddimensionspacepoint')
            ->from($this->tableNames->node(), 'pn')
            ->innerJoinTableSubquery('pn', $hierarchyRelationQuery->withPossibleParentNodeAggregateId($nodeAggregateIdCondition), 'ch', 'ch.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('ch', $this->tableNames->dimensionSpacePoints(), 'cdsp', 'cdsp.hash = ch.dimensionspacepointhash')
            ->innerJoin('ch', $this->tableNames->node(), 'cn', 'cn.relationanchorpoint = ch.childnodeanchor')
            ->whereCondition('pn', $nodeAggregateIdCondition)
            ->orderBy('ch.position');
    }

    public function buildFindRootNodeAggregatesQuery(HierarchyRelationSubquery $hierarchyRelationQuery, FindRootNodeAggregatesFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->buildBasicNodeAggregateQuery($hierarchyRelationQuery->withParentNodeRelationAnchor(
            NodeRelationAnchorPoint::forRootEdge()
        ));

        if ($filter->nodeTypeName !== null) {
            $queryBuilder->andWhere('n.nodetypename = :nodeTypeName')->setParameter('nodeTypeName', $filter->nodeTypeName->value);
        }

        return $queryBuilder;
    }

    public function buildBasicNodeQuery(HierarchyRelationSubquery $hierarchyRelationQuery, string $nodeTableAlias = 'n', string $select = 'n.*, h.subtreetags'): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select($select)
            ->from($this->tableNames->node(), $nodeTableAlias)
            ->innerJoinTableSubquery($nodeTableAlias, $hierarchyRelationQuery, 'h', 'h.childnodeanchor = ' . $nodeTableAlias . '.relationanchorpoint');
    }

    public function buildBasicChildNodesQuery(HierarchyRelationSubquery $hierarchyRelationQuery, NodeAggregateId $parentNodeAggregateId): QueryBuilder
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($parentNodeAggregateId);

        return $this->createQueryBuilder()
            ->select('n.*, h.subtreetags')
            ->from($this->tableNames->node(), 'pn')
            ->innerJoinTableSubquery('pn', $hierarchyRelationQuery->withPossibleParentNodeAggregateId($nodeAggregateIdCondition), 'h', 'h.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNames->node(), 'n', 'h.childnodeanchor = n.relationanchorpoint')
            ->whereCondition('pn', $nodeAggregateIdCondition);
    }

    public function buildBasicParentNodeQuery(HierarchyRelationSubquery $hierarchyRelationQuery, NodeAggregateId $childNodeAggregateId): QueryBuilder
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($childNodeAggregateId);

        return $this->createQueryBuilder()
            ->select('pn.*, ch.subtreetags')
            ->from($this->tableNames->node(), 'pn')
            ->innerJoinTableSubquery('pn', $hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'ph', 'ph.parentnodeanchor = pn.relationanchorpoint')
            ->innerJoin('pn', $this->tableNames->node(), 'cn', 'cn.relationanchorpoint = ph.childnodeanchor')
            ->innerJoinTableSubquery('pn', $hierarchyRelationQuery, 'ch', 'ch.childnodeanchor = pn.relationanchorpoint')
            ->whereCondition('cn', $nodeAggregateIdCondition);
    }

    public function buildBasicNodeSiblingsQuery(HierarchyRelationSubquery $hierarchyRelationQuery, bool $preceding, NodeAggregateId $siblingNodeAggregateId): QueryBuilder
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($siblingNodeAggregateId);

        $sharedSubQuery = $this->createQueryBuilder()
            ->fromTableSubquery($hierarchyRelationQuery->withPossibleChildNodeAggregateId($nodeAggregateIdCondition), 'sh')
            ->innerJoin('sh', $this->tableNames->node(), 'sn', 'sn.relationanchorpoint = sh.childnodeanchor')
            ->whereCondition('sn', $nodeAggregateIdCondition);

        $parentNodeAnchorSubQuery = (clone $sharedSubQuery)->select('sh.parentnodeanchor');
        $siblingPositionSubQuery = (clone $sharedSubQuery)->select('sh.position');

        return $this->buildBasicNodeQuery($hierarchyRelationQuery)
            ->andWhere('h.parentnodeanchor = (' . $parentNodeAnchorSubQuery->getSQL() . ')')
            ->andWhere('n.nodeaggregateid != ' . $nodeAggregateIdCondition->getParameters()->getReference('nodeAggregateId'))
            ->andWhere('h.position ' . ($preceding ? '<' : '>') . ' (' . $siblingPositionSubQuery->getSQL() . ')')
            ->mergeParametersFromBuilder($sharedSubQuery)
            ->orderBy('h.position', $preceding ? 'DESC' : 'ASC');
    }

    public function addNodeTypeCriteria(QueryBuilder $queryBuilder, ExpandedNodeTypeCriteria $constraintsWithSubNodeTypes, string $nodeTableAlias = 'n'): void
    {
        $nodeTablePrefix = $nodeTableAlias === '' ? '' : $nodeTableAlias . '.';
        $allowanceQueryPart = '';
        if (!$constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->isEmpty()) {
            $allowanceQueryPart = $queryBuilder->expr()->in($nodeTablePrefix . 'nodetypename', ':explicitlyAllowedNodeTypeNames');
            $queryBuilder->setParameter('explicitlyAllowedNodeTypeNames', $constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->toStringArray(), ArrayParameterType::STRING);
        }
        $denyQueryPart = '';
        if (!$constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $denyQueryPart = $queryBuilder->expr()->notIn($nodeTablePrefix . 'nodetypename', ':explicitlyDisallowedNodeTypeNames');
            $queryBuilder->setParameter('explicitlyDisallowedNodeTypeNames', $constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->toStringArray(), ArrayParameterType::STRING);
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
        if ($searchTerm->term === '') {
            return;
        }
        $queryBuilder->andWhere('JSON_SEARCH(' . $nodeTableAlias . '.properties, "one", :searchTermPattern COLLATE ' . DbalSchemaFactory::DEFAULT_MYSQL_COLLATION . ', NULL, "$.*.value") IS NOT NULL')->setParameter('searchTermPattern', '%' . $searchTerm->term . '%');
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
        if (gettype($value) === 'boolean') {
            return $this->extractPropertyValue($propertyName, $nodeTableAlias) . ' ' . $operator . ($value ? 'true' : 'false');
        }

        if (gettype($value) === 'integer') {
            return $this->extractPropertyValue($propertyName, $nodeTableAlias) . ' ' . $operator . $value;
        }

        if (gettype($value) === 'double') {
            return $this->extractPropertyValue($propertyName, $nodeTableAlias) . ' ' . $operator . $value;
        }

        $paramName = $this->createUniqueParameterName();
        $queryBuilder->setParameter($paramName, $value);
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

    private function searchPropertyValueStatement(QueryBuilder $queryBuilder, PropertyName $propertyName, string $value, string $nodeTableAlias, bool $caseSensitive): string
    {
        try {
            $escapedPropertyName = addslashes(json_encode($propertyName->value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to escape property name: %s', $e->getMessage()), 1679394579, $e);
        }

        $paramName = $this->createUniqueParameterName();
        $queryBuilder->setParameter($paramName, $value);

        if ($caseSensitive) {
            return 'JSON_SEARCH(' . $nodeTableAlias . '.properties COLLATE utf8mb4_bin, \'one\', :' . $paramName . ' COLLATE utf8mb4_bin, NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
        }

        return 'JSON_SEARCH(' . $nodeTableAlias . '.properties COLLATE ' . DbalSchemaFactory::DEFAULT_MYSQL_COLLATION . ', \'one\', :' . $paramName . ' COLLATE ' . DbalSchemaFactory::DEFAULT_MYSQL_COLLATION . ', NULL, \'$.' . $escapedPropertyName . '.value\') IS NOT NULL';
    }

    public function buildFindUsedNodeTypeNamesQuery(): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select('DISTINCT nodetypename')
            ->from($this->tableNames->node());
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return QueryBuilder::createForConnection($this->connection);
    }

    private function createUniqueParameterName(): string
    {
        return 'param_' . str_replace('-', '', UuidFactory::create());
    }
}
