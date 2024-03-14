<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;

/**
 * @internal
 */
readonly class NodeFilterCriteria
{
    public function __construct(
        public ?NodeTypeCriteria $nodeTypeCriteria = null,
        public ?PropertyValueCriteriaInterface $propertyValueCriteria = null) {
    }
}
