<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * The active record for reading and writing references in-memory
 *
 * @internal
 */
final class InMemoryReferenceRecord
{
    public function __construct(
        public InMemoryNodeRecord $source,
        public ReferenceName $name,
        public ?SerializedPropertyValues $properties,
        public InMemoryNodeRecord $target,
    ) {
    }
}
