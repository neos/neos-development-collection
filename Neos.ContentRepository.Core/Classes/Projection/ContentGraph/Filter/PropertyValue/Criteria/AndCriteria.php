<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

/**
 * Binary operation that conjunctively combines two criteria:
 *   "prop1 = 'foo' AND prop2 = 'bar'"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final readonly class AndCriteria implements PropertyValueCriteriaInterface
{
    private function __construct(
        public PropertyValueCriteriaInterface $criteria1,
        public PropertyValueCriteriaInterface $criteria2,
    ) {
    }

    public static function create(PropertyValueCriteriaInterface $criteria1, PropertyValueCriteriaInterface $criteria2): self
    {
        return new self($criteria1, $criteria2);
    }
}
