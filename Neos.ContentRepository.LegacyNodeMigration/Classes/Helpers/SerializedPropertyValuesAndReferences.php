<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
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
