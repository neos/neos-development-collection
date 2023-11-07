<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\Filter;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Utility\Arrays;

readonly class NodeFilterCriteriaGroupFactory
{
    public static function createFromFizzleExpressionString (string $fizzleExpression): ?NodeFilterCriteriaGroup
    {
        // ensure absolute node pathes are ignored as the fizzle parser cannot handle those yet
        // @todo remove once the parser can handle those
        $parts = Arrays::trimExplode(',', $fizzleExpression);
        foreach ($parts as $part) {
            if (AbsoluteNodePath::patternIsMatchedByString($part)) {
                return null;
            }
        }
        $parsedFilter = FizzleParser::parseFilterGroup($fizzleExpression);
        return self::createFromParsedFizzleExpression($parsedFilter);
    }

    /**
     * @param mixed[] $parsedFizzleExpression
     */
    private static function createFromParsedFizzleExpression (array $parsedFizzleExpression): ?NodeFilterCriteriaGroup
    {
        $filterCriteria = [];
        if (is_array($parsedFizzleExpression)
            && array_key_exists('name', $parsedFizzleExpression) && $parsedFizzleExpression['name'] === 'FilterGroup'
            && array_key_exists('Filters', $parsedFizzleExpression) && is_array($parsedFizzleExpression['Filters'])
        ) {
            foreach ($parsedFizzleExpression['Filters'] as $filter) {
                // anything but AttributeFilters yield a null result
                if (array_key_exists('PathFilter', $filter)
                    || array_key_exists('PropertyNameFilter', $filter)
                    || array_key_exists('IdentifierFilter', $filter)
                ) {
                    return null;
                }
                if (array_key_exists('AttributeFilters', $filter) && is_array ($filter['AttributeFilters'])) {

                    $allowedNodeTypeNames = NodeTypeNames::createEmpty();
                    $disallowedNodeTypeNames = NodeTypeNames::createEmpty();

                    /**
                     * @var PropertyValueCriteriaInterface[]
                     */
                    $propertyCriteria = [];
                    foreach ($filter['AttributeFilters'] as $attributeFilter) {
                        $propertyPath = $attributeFilter['PropertyPath'] ?? null;
                        $operator = $attributeFilter['Operator'] ?? null;
                        $operand = $attributeFilter['Operand'] ?? null;
                        switch ($operator) {
                            case 'instanceof':
                                $allowedNodeTypeNames = $allowedNodeTypeNames->withAdditionalNodeTypeName(NodeTypeName::fromString($operand));
                                break;
                            case '!instanceof':
                                $disallowedNodeTypeNames = $disallowedNodeTypeNames->withAdditionalNodeTypeName(NodeTypeName::fromString($operand));
                                break;
                            case '=':
                                $propertyCriteria[] = PropertyValueEquals::create(PropertyName::fromString($propertyPath), $operand, true);
                                break;
                            case '!=':
                                $propertyCriteria[] = NegateCriteria::create(PropertyValueEquals::create(PropertyName::fromString($propertyPath), $operand, true));
                                break;
                            case '^=':
                                $propertyCriteria[] = PropertyValueStartsWith::create(PropertyName::fromString($propertyPath), $operand, true);
                                break;
                            case '$=':
                                $propertyCriteria[] = PropertyValueEndsWith::create(PropertyName::fromString($propertyPath), $operand, true);
                                break;
                            case '*=':
                                $propertyCriteria[] = PropertyValueContains::create(PropertyName::fromString($propertyPath), $operand, true);
                                break;
                            case '=~':
                                $propertyCriteria[] = PropertyValueEquals::create(PropertyName::fromString($propertyPath), $operand, false);
                                break;
                            case '!=~':
                                $propertyCriteria[] = NegateCriteria::create(PropertyValueEquals::create(PropertyName::fromString($propertyPath), $operand, false));
                                break;
                            case '^=~':
                                $propertyCriteria[] = PropertyValueStartsWith::create(PropertyName::fromString($propertyPath), $operand, false);
                                break;
                            case '$=~':
                                $propertyCriteria[] = PropertyValueEndsWith::create(PropertyName::fromString($propertyPath), $operand, false);
                                break;
                            case '*=~':
                                $propertyCriteria[] = PropertyValueContains::create(PropertyName::fromString($propertyPath), $operand, false);
                                break;
                            case '>':
                                $propertyCriteria[] = PropertyValueGreaterThan::create(PropertyName::fromString($propertyPath), $operand);
                                break;
                            case '>=':
                                $propertyCriteria[] = PropertyValueGreaterThanOrEqual::create(PropertyName::fromString($propertyPath), $operand);
                                break;
                            case '<':
                                $propertyCriteria[] = PropertyValueLessThan::create(PropertyName::fromString($propertyPath), $operand);
                                break;
                            case '<=':
                                $propertyCriteria[] = PropertyValueLessThanOrEqual::create(PropertyName::fromString($propertyPath), $operand);
                                break;
                            default:
                                return null;
                        }
                    }

                    if (count($propertyCriteria) > 1) {
                        $propertyCriteriaCombined = array_shift($propertyCriteria);
                        while ($other = array_shift($propertyCriteria)) {
                            $propertyCriteriaCombined = AndCriteria::create($propertyCriteriaCombined, $other);
                        }
                    } elseif (count($propertyCriteria) == 1) {
                        $propertyCriteriaCombined = $propertyCriteria[0];
                    } else {
                        $propertyCriteriaCombined = null;
                    }

                    $filterCriteria[] = new NodeFilterCriteria(
                        ($allowedNodeTypeNames->isEmpty() && $disallowedNodeTypeNames->isEmpty()) ? null : NodeTypeCriteria::create($allowedNodeTypeNames, $disallowedNodeTypeNames),
                        $propertyCriteriaCombined
                    );
                } else {
                    return null;
                }
            }
            return new NodeFilterCriteriaGroup(...$filterCriteria);
        }
        return null;
    }
}
