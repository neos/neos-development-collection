<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\SqlWhereConditionInterface;

/**
 * @internal
 */
final readonly class NodeTypeCriteriaCondition implements SqlWhereConditionInterface
{
    private function __construct(
        private ExpandedNodeTypeCriteria $constraintsWithSubNodeTypes,
    ) {
    }

    public static function create(ExpandedNodeTypeCriteria $constraintsWithSubNodeTypes): self
    {
        return new self(
            $constraintsWithSubNodeTypes
        );
    }

    public function getParameters(): Parameters
    {
        $parameters = [];
        if (!$this->constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->isEmpty()) {
            $parameters[] = Parameter::stringArray('explicitlyAllowedNodeTypeNames', $this->constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->toStringArray());
        }
        if (!$this->constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $parameters[] = Parameter::stringArray('explicitlyDisallowedNodeTypeNames', $this->constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->toStringArray());
        }
        return Parameters::create(...$parameters);
    }

    public function toWhereSql(string $alias): string
    {
        $prefix = $alias !== '' ? "$alias." : '';

        $allowanceQueryPart = '';
        if (!$this->constraintsWithSubNodeTypes->explicitlyAllowedNodeTypeNames->isEmpty()) {
            $allowanceQueryPart = "{$prefix}nodetypename IN (:explicitlyAllowedNodeTypeNames)";
        }
        $denyQueryPart = '';
        if (!$this->constraintsWithSubNodeTypes->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $denyQueryPart = "{$prefix}nodetypename NOT IN (:explicitlyDisallowedNodeTypeNames)";
        }
        if ($allowanceQueryPart && $denyQueryPart) {
            if ($this->constraintsWithSubNodeTypes->isWildCardAllowed) {
                return "({$allowanceQueryPart} OR {$denyQueryPart})";
            }
            return "({$allowanceQueryPart} AND {$denyQueryPart})";
        }

        if ($allowanceQueryPart && !$this->constraintsWithSubNodeTypes->isWildCardAllowed) {
            return $allowanceQueryPart;
        }
        if ($denyQueryPart) {
            return $denyQueryPart;
        }

        return '1 = 1';
    }
}
