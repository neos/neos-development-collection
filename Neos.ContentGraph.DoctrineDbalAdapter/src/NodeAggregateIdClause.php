<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;

/**
 * @internal
 */
final readonly class NodeAggregateIdClause
{
    private function __construct(
        private NodeAggregateIds $nodeAggregateIds
    ) {
    }

    public static function forNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        return new self(
            NodeAggregateIds::create($nodeAggregateId)
        );
    }

    public static function forNodeAggregateIds(NodeAggregateIds $nodeAggregateIds): self
    {
        if ($nodeAggregateIds->isEmpty()) {
            throw new \InvalidArgumentException('Node aggregate ids to filter must not be empty', 1781553575);
        }
        return new self(
            $nodeAggregateIds
        );
    }

    public function getParameters(): Parameters
    {
        return Parameters::create(
            $this->nodeAggregateIds->count() === 1
                ? Parameter::string('nodeAggregateId', $this->nodeAggregateIds->toStringArray()[0])
                : Parameter::stringArray('nodeAggregateIds', $this->nodeAggregateIds->toStringArray())
        );
    }

    public function toWhereSql(string $alias = 'n'): string
    {
        $prefix = $alias !== '' ? "$alias." : '';

        return $this->nodeAggregateIds->count() === 1
            ? "{$prefix}nodeaggregateid = :nodeAggregateId"
            : "{$prefix}nodeaggregateid IN (:nodeAggregateIds)";
    }

    public function toRelationAnchorPointSubquerySql(ContentGraphTableNames $tableNames): string
    {
        return "(SELECT relationanchorpoint FROM {$tableNames->node()} WHERE {$this->toWhereSql('')})";
    }
}
