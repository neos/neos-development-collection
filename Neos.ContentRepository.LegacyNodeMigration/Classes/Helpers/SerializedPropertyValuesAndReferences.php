<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class SerializedPropertyValuesAndReferences
{

    /**
     * @param array<string, NodeAggregateIds> $references
     */
    public function __construct(
        public SerializedPropertyValues $serializedPropertyValues,
        public array $references,
    ) {}
}
