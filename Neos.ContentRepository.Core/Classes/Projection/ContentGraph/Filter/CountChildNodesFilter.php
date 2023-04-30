<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::countChildNodes()}
 *
 * Example:
 *
 * // create a new instance and set node type constraints
 * CountChildNodesFilter::create(nodeTypeConstraints: 'Some.Included:NodeType,!Some.Excluded:NodeType');
 *
 * // create an instance from an existing FindChildNodesFilter instance
 * CountChildNodesFilter::fromFindChildNodesFilter($filter);
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class CountChildNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?SearchTerm $searchTerm,
        public readonly ?PropertyValueCriteriaInterface $propertyValue,
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        SearchTerm|string $searchTerm = null,
        PropertyValueCriteriaInterface|string $propertyValue = null,
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        if (is_string($searchTerm)) {
            $searchTerm = SearchTerm::fulltext($searchTerm);
        }
        if (is_string($propertyValue)) {
            $propertyValue = PropertyValueCriteriaParser::parse($propertyValue);
        }
        return new self($nodeTypeConstraints, $searchTerm, $propertyValue);
    }

    public static function fromFindChildNodesFilter(FindChildNodesFilter $filter): self
    {
        return new self($filter->nodeTypeConstraints, $filter->searchTerm, $filter->propertyValue);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        SearchTerm|string $searchTerm = null,
        PropertyValueCriteriaInterface|string $propertyValue = null,
    ): self {
        return self::create(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $searchTerm ?? $this->searchTerm,
            $propertyValue ?? $this->propertyValue,
        );
    }
}
