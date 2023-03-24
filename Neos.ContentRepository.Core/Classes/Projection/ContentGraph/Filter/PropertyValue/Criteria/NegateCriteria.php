<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;

/**
 * Unary operation that negates a criteria:
 *   "NOT (prop1 = 'foo' OR prop1 = 'bar')"
 * Or:
 *   "prop1 != 'foo'"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final class NegateCriteria implements PropertyValueCriteriaInterface
{
    private function __construct(
        public readonly PropertyValueCriteriaInterface $criteria,
    ) {
    }

    public static function create(PropertyValueCriteriaInterface $criteria): self
    {
        return new self($criteria);
    }
}
