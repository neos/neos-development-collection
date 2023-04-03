<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\TimestampField;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::findDescendantNodes()}
 *
 * Example:
 *
 * FindDescendantNodesFilter::create()->with(nodeTypeConstraint: 'Some.Included:NodeType,!Some.Excluded:NodeType', searchTerm: 'foo');
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class FindDescendantNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?SearchTerm $searchTerm,
        public readonly ?PropertyValueCriteriaInterface $propertyValue,
        public readonly ?Ordering $ordering,
    ) {
    }

    public static function create(): self
    {
        return new self(null, null, null, null);
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
     *
     * @param Ordering|array<string, mixed>|null $ordering
     */
    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        SearchTerm|string $searchTerm = null,
        PropertyValueCriteriaInterface|string $propertyValue = null,
        Ordering|array $ordering = null,
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
        if (is_array($ordering)) {
            $ordering = Ordering::fromArray($ordering);
        }
        return new self(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $searchTerm ?? $this->searchTerm,
            $propertyValue ?? $this->propertyValue,
            $ordering ?? $this->ordering,
        );
    }

    public function withNodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        return $this->with(nodeTypeConstraints: $nodeTypeConstraints);
    }

    public function withSearchTerm(SearchTerm|string $searchTerm): self
    {
        return $this->with(searchTerm: $searchTerm);
    }

    public function withOrderByProperty(PropertyName $propertyName, OrderingDirection $direction): self
    {
        return $this->with(ordering: Ordering::byProperty($propertyName, $direction));
    }

    public function withOrderByTimestampField(TimestampField $timestampField, OrderingDirection $direction): self
    {
        return $this->with(ordering: Ordering::byTimestampField($timestampField, $direction));
    }
}
