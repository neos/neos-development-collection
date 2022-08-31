<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class SerializedPropertyValuesAndReferences
{

    /**
     * @param array<string, NodeAggregateIdentifiers> $references
     */
    public function __construct(
        public readonly SerializedPropertyValues $serializedPropertyValues,
        public readonly array $references,
    ) {}
}
