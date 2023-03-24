<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::countDescendantNodes()}
 *
 * Example:
 *
 * // create a new instance and set node type constraints and search term
 * CountDescendantNodesFilter::create()->with(nodeTypeConstraint: 'Some.Included:NodeType,!Some.Excluded:NodeType', searchTerm: 'foo');
 *
 * // create an instance from an existing FindChildNodesFilter instance
 * CountDescendantNodesFilter::fromFindChildNodesFilter($filter);
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class CountDescendantNodesFilter
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

    public static function create(): self
    {
        return new self(null, null, null);
    }

    public static function fromFindDescendantNodesFilter(FindDescendantNodesFilter $filter): self
    {
        return new self($filter->nodeTypeConstraints, $filter->searchTerm, $filter->propertyValue);
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        return self::create()->with(nodeTypeConstraints: $nodeTypeConstraints);
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
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        if (is_string($searchTerm)) {
            $searchTerm = SearchTerm::fulltext($searchTerm);
        }
        if (is_string($propertyValue)) {
            $propertyValue = PropertyValueCriteriaParser::parse($propertyValue);
        }
        return new self(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $searchTerm ?? $this->searchTerm,
            $propertyValue ?? $this->propertyValue,
        );
    }

    public function withSearchTerm(SearchTerm|string $searchTerm): self
    {
        return $this->with(searchTerm: $searchTerm);
    }
}
