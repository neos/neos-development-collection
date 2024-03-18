<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::countReferences()}
 *
 * Example:
 *
 * // create a new instance and set reference name filter
 * CountReferencesFilter::create(referenceName: 'someName');
 *
 * // create an instance from an existing FindReferencesFilter instance
 * CountReferencesFilter::fromFindReferencesFilter($filter);
 *
 * NOTE:
 * "nodeSearchTerm" and "nodePropertyValue" are applied for the properties of the target node
 * "referenceSearchTerm" and "referencePropertyValue" are applied to the properties of the reference itself
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class CountReferencesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeCriteria $nodeTypes,
        public readonly ?SearchTerm $nodeSearchTerm,
        public readonly ?PropertyValueCriteriaInterface $nodePropertyValue,
        public readonly ?SearchTerm $referenceSearchTerm,
        public readonly ?PropertyValueCriteriaInterface $referencePropertyValue,
        public readonly ?ReferenceName $referenceName,
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        NodeTypeCriteria|string $nodeTypes = null,
        SearchTerm|string $nodeSearchTerm = null,
        PropertyValueCriteriaInterface|string $nodePropertyValue = null,
        SearchTerm|string $referenceSearchTerm = null,
        PropertyValueCriteriaInterface|string $referencePropertyValue = null,
        ReferenceName|string $referenceName = null,
    ): self {
        if (is_string($nodeTypes)) {
            $nodeTypes = NodeTypeCriteria::fromFilterString($nodeTypes);
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
        return new self($nodeTypes, $nodeSearchTerm, $nodePropertyValue, $referenceSearchTerm, $referencePropertyValue, $referenceName);
    }

    public static function fromFindReferencesFilter(FindReferencesFilter $filter): self
    {
        return new self($filter->nodeTypes, $filter->nodeSearchTerm, $filter->nodePropertyValue, $filter->referenceSearchTerm, $filter->referencePropertyValue, $filter->referenceName);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        NodeTypeCriteria|string $nodeTypes = null,
        SearchTerm|string $nodeSearchTerm = null,
        PropertyValueCriteriaInterface|string $nodePropertyValue = null,
        SearchTerm|string $referenceSearchTerm = null,
        PropertyValueCriteriaInterface|string $referencePropertyValue = null,
        ReferenceName|string $referenceName = null,
    ): self {
        return self::create(
            $nodeTypes ?? $this->nodeTypes,
            $nodeSearchTerm ?? $this->nodeSearchTerm,
            $nodePropertyValue ?? $this->nodePropertyValue,
            $referenceSearchTerm ?? $this->referenceSearchTerm,
            $referencePropertyValue ?? $this->referencePropertyValue,
            $referenceName ?? $this->referenceName,
        );
    }
}
