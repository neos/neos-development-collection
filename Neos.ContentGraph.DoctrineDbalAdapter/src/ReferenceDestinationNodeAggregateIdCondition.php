<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\SqlWhereConditionInterface;

/**
 * @internal
 */
final readonly class ReferenceDestinationNodeAggregateIdCondition implements SqlWhereConditionInterface
{
    private function __construct(
        private NodeAggregateId $nodeAggregateId
    ) {
    }

    public static function forNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        return new self(
            $nodeAggregateId
        );
    }

    public function getParameters(): Parameters
    {
        return Parameters::create(
            Parameter::string('nodeAggregateId', $this->nodeAggregateId->value)
        );
    }

    public function toWhereSql(string $alias): string
    {
        $prefix = $alias !== '' ? "$alias." : '';

        return "{$prefix}destinationnodeaggregateid = :nodeAggregateId";
    }

    public function toRelationAnchorPointSubquerySql(ContentGraphTableNames $tableNames): string
    {
        return "(SELECT nodeanchorpoint FROM {$tableNames->referenceRelation()} WHERE {$this->toWhereSql('')})";
    }
}
