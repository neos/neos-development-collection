<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class SerializedPropertyValuesAndReferences
{
    public function __construct(
        public SerializedPropertyValues $serializedPropertyValues,
        public SerializedNodeReferences $references,
    ) {}
}
