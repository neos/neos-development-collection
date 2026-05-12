<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The difference between two reference read models
 */
#[Flow\Proxy(false)]
final readonly class ReferenceDiff implements \JsonSerializable
{
    private function __construct(
        public ?NodeDiff $node,
        public ?ReferenceName $name,
        public ?PropertyDiff $properties,
        public bool $propertiesWereUnset,
    ) {
    }

    public static function tryCreate(
        ?NodeDiff $node = null,
        ?ReferenceName $name = null,
        ?PropertyDiff $properties = null,
        bool $propertiesWereUnset = false,
    ): ?self {
        if (
            $node === null
            && $name === null
            && $properties === null
            && $propertiesWereUnset === false
        ) {
            return null;
        }

        return new self(
            node: $node,
            name: $name,
            properties: $properties,
            propertiesWereUnset: $propertiesWereUnset,
        );
    }

    public static function forAnAdditionalNode(Reference $reference): self
    {
        return new self(
            node: NodeDiff::forAnAdditionalNode($reference->node),
            name: $reference->name,
            properties: PropertyDiff::tryForAnAdditionalNode($reference->properties->serialized()),
            propertiesWereUnset: false,
        );
    }

    public static function tryFromReferenceComparison(
        Reference $referenceToCompare,
        Reference $referenceReference,
        ?WorkspaceName $expectedWorkspaceName,
    ): ?self {
        $differentNode = NodeDiff::tryFromNodeComparison($referenceToCompare->node, $referenceReference->node, $expectedWorkspaceName);
        $differentName = $referenceToCompare->name->equals($referenceReference->name)
            ? null
            : $referenceToCompare->name;

        if ($referenceToCompare->properties) {
            $propertiesWereUnset = false;
            if ($referenceReference->properties) {
                $differentProperties = PropertyDiff::tryFromNodeComparison(
                    $referenceToCompare->properties->serialized(),
                    $referenceReference->properties->serialized(),
                );
            } else {
                $differentProperties = PropertyDiff::tryForAnAdditionalNode($referenceToCompare->properties->serialized());
            }
        } else {
            $propertiesWereUnset = $referenceReference->properties instanceof PropertyCollection;
            $differentProperties = null;
        }

        return self::tryCreate(
            node: $differentNode,
            name: $differentName,
            properties: $differentProperties,
            propertiesWereUnset: $propertiesWereUnset,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            get_object_vars($this),
            fn (mixed $value): bool => $value !== null && $value !== false,
        );
    }
}
