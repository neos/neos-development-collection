<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::findReferences()}
 *
 * Example:
 *
 * FindReferencesFilter::create(referenceName: 'someName');
 *
 * NOTE:
 * "nodeSearchTerm", "nodePropertyValue" and "ordering" are applied for the properties of the target node
 * "referenceSearchTerm" and "referencePropertyValue" are applied to the properties of the reference itself
 * @api for the factory methods; NOT for the inner state.
 */
final class FindReferencesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?SearchTerm $nodeSearchTerm,
        public readonly ?PropertyValueCriteriaInterface $nodePropertyValue,
        public readonly ?SearchTerm $referenceSearchTerm,
        public readonly ?PropertyValueCriteriaInterface $referencePropertyValue,
        public readonly ?ReferenceName $referenceName,
        public readonly ?Ordering $ordering,
        public readonly ?Pagination $pagination,
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
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        SearchTerm|string $nodeSearchTerm = null,
        PropertyValueCriteriaInterface|string $nodePropertyValue = null,
        SearchTerm|string $referenceSearchTerm = null,
        PropertyValueCriteriaInterface|string $referencePropertyValue = null,
        ReferenceName|string $referenceName = null,
        Ordering|array $ordering = null,
        Pagination|array $pagination = null,
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        if (is_string($nodeSearchTerm)) {
            $nodeSearchTerm = SearchTerm::fulltext($nodeSearchTerm);
        }
        if (is_string($nodePropertyValue)) {
            $nodePropertyValue = PropertyValueCriteriaParser::parse($nodePropertyValue);
        }
        if (is_string($referenceSearchTerm)) {
            $referenceSearchTerm = SearchTerm::fulltext($referenceSearchTerm);
        }
        if (is_string($referencePropertyValue)) {
            $referencePropertyValue = PropertyValueCriteriaParser::parse($referencePropertyValue);
        }
        if (is_string($referenceName)) {
            $referenceName = ReferenceName::fromString($referenceName);
        }
        if (is_array($ordering)) {
            $ordering = Ordering::fromArray($ordering);
        }
        if (is_array($pagination)) {
            $pagination = Pagination::fromArray($pagination);
        }
        return new self($nodeTypeConstraints, $nodeSearchTerm, $nodePropertyValue, $referenceSearchTerm, $referencePropertyValue, $referenceName, $ordering, $pagination);
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
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        SearchTerm|string $nodeSearchTerm = null,
        PropertyValueCriteriaInterface|string $nodePropertyValue = null,
        SearchTerm|string $referenceSearchTerm = null,
        PropertyValueCriteriaInterface|string $referencePropertyValue = null,
        ReferenceName|string $referenceName = null,
        Ordering|array $ordering = null,
        Pagination|array $pagination = null,
    ): self {
        return self::create(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $nodeSearchTerm ?? $this->nodeSearchTerm,
            $nodePropertyValue ?? $this->nodePropertyValue,
            $referenceSearchTerm ?? $this->referenceSearchTerm,
            $referencePropertyValue ?? $this->referencePropertyValue,
            $referenceName ?? $this->referenceName,
            $ordering ?? $this->ordering,
            $pagination ?? $this->pagination,
        );
    }
}
