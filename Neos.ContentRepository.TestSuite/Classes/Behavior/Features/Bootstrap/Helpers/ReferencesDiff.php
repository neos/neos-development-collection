<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The difference between two nodes read models
 */
#[Flow\Proxy(false)]
final readonly class ReferencesDiff implements \JsonSerializable
{
    /**
     * @param array<int,ReferenceDiff|ReferenceDiscriminator> $references Complete reference diffs for added references, arbitrary ones for modified ones, ReferenceDiscriminator for unmodified ones
     */
    private function __construct(
        public ?array $references,
        public ?ReferenceDiscriminators $removedReferences,
    ) {
    }

    /**
     * @param array<int,ReferenceDiff|ReferenceDiscriminator> $references
     */
    public static function tryCreate(
        ?array $references = null,
        ?ReferenceDiscriminators $removedReferences = null,
    ): ?self {
        if (
            $references === null
            && $removedReferences === null
        ) {
            return null;
        }

        return new self(
            references: $references,
            removedReferences: $removedReferences,
        );
    }

    public static function tryForAnAdditionalNode(References $references): ?self
    {
        return self::tryCreate(
            references: $references->references === []
                ? null
                : array_map(
                    fn (Reference $reference): ReferenceDiff => ReferenceDiff::forAnAdditionalNode($reference),
                    $references->references,
                ),
        );
    }

    public static function tryFromReferencesComparison(
        References $referencesToCompare,
        References $referenceReferences,
        ?WorkspaceName $expectedWorkspaceName,
    ): ?self {
        $references = [];
        foreach ($referencesToCompare as $referenceToCompare) {
            $referenceReference = null;
            foreach ($referenceReferences as $availableReferenceReference) {
                if (
                    ReferenceDiscriminator::fromReference($availableReferenceReference)
                        ->equals(ReferenceDiscriminator::fromReference($referenceToCompare))
                ) {
                    $referenceReference = $availableReferenceReference;
                    break;
                }
            }
            if ($referenceReference) {
                $references[] = ReferenceDiff::tryFromReferenceComparison($referenceToCompare, $referenceReference, $expectedWorkspaceName)
                    ?: ReferenceDiscriminator::fromReference($referenceToCompare);
            } else {
                $references[] = ReferenceDiff::forAnAdditionalNode($referenceToCompare);
            }
        }

        $removedReferences = [];
        foreach ($referenceReferences as $availableReferenceReference) {
            $referenceToCompare = null;
            foreach ($referencesToCompare as $availableReferenceToCompare) {
                if (
                    ReferenceDiscriminator::fromReference($availableReferenceToCompare)
                        ->equals(ReferenceDiscriminator::fromReference($availableReferenceReference))
                ) {
                    $referenceToCompare = $availableReferenceReference;
                    break;
                }
            }
            if ($referenceToCompare === null) {
                $removedReferences[] = ReferenceDiscriminator::fromReference($availableReferenceReference);
            }
        }

        // if nothing changed, then each reference is only represented as its discriminator and the order is the same
        $referencesIfNothingChanged = ReferenceDiscriminators::fromReferences($referenceReferences)->items;

        if (
            $references == $referencesIfNothingChanged
            && $removedReferences === []
        ) {
            return null;
        }

        return new self(
            references: $references,
            removedReferences: $removedReferences === [] ? null : ReferenceDiscriminators::list(...$removedReferences),
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
