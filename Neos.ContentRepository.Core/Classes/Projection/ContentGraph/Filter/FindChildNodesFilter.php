<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTerm;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::findChildNodes()}
 *
 * Example:
 *
 * FindChildNodesFilter::create(nodeTypes: 'Some.Included:NodeType,!Some.Excluded:NodeType');
 *
 * @api for the factory methods; NOT for the inner state.
 */
final readonly class FindChildNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public ?NodeTypeCriteria $nodeTypes,
        public ?SearchTerm $searchTerm,
        public ?PropertyValueCriteriaInterface $propertyValue,
        public ?Ordering $ordering,
        public ?Pagination $pagination,
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param Ordering|array<string, mixed>|null $ordering
     * @param Pagination|array<string, mixed>|null $pagination
     */
    public static function create(
        NodeTypeCriteria|string $nodeTypes = null,
        SearchTerm|string $searchTerm = null,
        PropertyValueCriteriaInterface|string $propertyValue = null,
        Ordering|array $ordering = null,
        Pagination|array $pagination = null,
    ): self {
        if (is_string($nodeTypes)) {
            $nodeTypes = NodeTypeCriteria::fromFilterString($nodeTypes);
        }
        if (is_string($searchTerm)) {
            $searchTerm = SearchTerm::fulltext($searchTerm);
        }
        if (is_string($propertyValue)) {
            $propertyValue = PropertyValueCriteriaParser::parse($propertyValue);
        }
        if (is_array($ordering)) {
            $ordering = Ordering::fromArray($ordering);
        }
        if (is_array($pagination)) {
            $pagination = Pagination::fromArray($pagination);
        }
        return new self($nodeTypes, $searchTerm, $propertyValue, $ordering, $pagination);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param Ordering|array<string, mixed>|null $ordering
     * @param Pagination|array<string, mixed>|null $pagination
     */
    public function with(
        NodeTypeCriteria|string $nodeTypes = null,
        SearchTerm|string $searchTerm = null,
        PropertyValueCriteriaInterface|string $propertyValue = null,
        Ordering|array $ordering = null,
        Pagination|array $pagination = null,
    ): self {
        return self::create(
            $nodeTypes ?? $this->nodeTypes,
            $searchTerm ?? $this->searchTerm,
            $propertyValue ?? $this->propertyValue,
            $ordering ?? $this->ordering,
            $pagination ?? $this->pagination,
        );
    }
}
