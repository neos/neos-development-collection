<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

/**
 * Binary operation that disjunctively combines two criteria:
 *   "prop1 = 'foo' OR prop2 = 'bar'"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final class OrCriteria implements PropertyValueCriteriaInterface
{
    private function __construct(
        public readonly PropertyValueCriteriaInterface $criteria1,
        public readonly PropertyValueCriteriaInterface $criteria2,
    ) {
    }

    public static function create(PropertyValueCriteriaInterface $criteria1, PropertyValueCriteriaInterface $criteria2): self
    {
        return new self($criteria1, $criteria2);
    }
}
