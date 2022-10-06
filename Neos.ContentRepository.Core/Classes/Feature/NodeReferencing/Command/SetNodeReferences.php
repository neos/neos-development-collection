<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * Create a named reference from source to destination node
 *
 * @api commands are the write-API of the ContentRepository
 */
final class SetNodeReferences implements CommandInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $sourceNodeAggregateId,
        public readonly OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        public readonly ReferenceName $referenceName,
        public readonly NodeReferencesToWrite $references,
    ) {
    }
}
