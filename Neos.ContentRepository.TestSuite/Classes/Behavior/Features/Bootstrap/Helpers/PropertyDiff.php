<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\Flow\Annotations as Flow;

/**
 * The difference between two node read models
 */
#[Flow\Proxy(false)]
final readonly class PropertyDiff implements \JsonSerializable
{
    private function __construct(
        public ?SerializedPropertyValues $addedProperties,
        public ?SerializedPropertyValues $modifiedProperties,
        public ?PropertyNames $removedProperties,
    ) {
    }

    public static function tryCreate(
        ?SerializedPropertyValues $addedProperties = null,
        ?SerializedPropertyValues $modifiedProperties = null,
        ?PropertyNames $removedProperties = null,
    ): ?self {
        return $addedProperties === null && $modifiedProperties === null && $removedProperties === null
            ? null
            : new self(
                addedProperties: $addedProperties,
                modifiedProperties: $modifiedProperties,
                removedProperties: $removedProperties,
            );
    }

    public static function tryForAnAdditionalNode(SerializedPropertyValues $properties): ?self
    {
        return self::tryCreate(
            addedProperties: $properties->values !== [] ? $properties : null,
        );
    }

    public static function tryFromNodeComparison(
        SerializedPropertyValues $propertiesToCompare,
        SerializedPropertyValues $referenceProperties,
    ): ?self {
        $addedProperties = array_diff_key($propertiesToCompare->values, $referenceProperties->values);
        $addedProperties = $addedProperties !== []
            ? SerializedPropertyValues::fromArray($addedProperties)
            : null;

        $modifiedProperties = [];
        foreach (array_keys(array_intersect_key($referenceProperties->values, $propertiesToCompare->values)) as $propertyName) {
            if (
                $referenceProperties->values[$propertyName]->type !== $propertiesToCompare->values[$propertyName]->type
                || $referenceProperties->values[$propertyName]->value != $propertiesToCompare->values[$propertyName]->value
            ) {
                $modifiedProperties[$propertyName] = $propertiesToCompare->values[$propertyName];
            }
        }
        $modifiedProperties = $modifiedProperties !== []
            ? SerializedPropertyValues::fromArray($modifiedProperties)
            : null;

        $removedProperties = array_diff_key($referenceProperties->values, $propertiesToCompare->values);
        $removedProperties = $removedProperties !== []
            ? PropertyNames::fromArray(array_keys($removedProperties))
            : null;

        return $addedProperties === null && $modifiedProperties === null && $removedProperties === null
            ? null
            : self::tryCreate(
                addedProperties: $addedProperties,
                modifiedProperties: $modifiedProperties,
                removedProperties: $removedProperties,
            );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            get_object_vars($this),
            fn (mixed $value): bool => $value !== null,
        );
    }
}
