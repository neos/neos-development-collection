<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;

/**
 * @internal
 */
final readonly class ReferenceDestinationNodeAggregateIdCondition
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

    public function toRelationAnchorPointSubquerySql(ContentGraphTableNames $tableNames): string
    {
        return "(SELECT nodeanchorpoint FROM {$tableNames->referenceRelation()} WHERE destinationnodeaggregateid = :nodeAggregateId)";
    }
}
