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

    /**
     * @param ContentStreamId $contentStreamId The content stream in which the create operation is to be performed
     * @param NodeAggregateId $sourceNodeAggregateId The identifier of the node aggregate to set references
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint The dimension space for which the references should be set
     * @param ReferenceName $referenceName Name of the reference to set
     * @param NodeReferencesToWrite $references Unserialized reference(s) to set
     */
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $sourceNodeAggregateId,
        public readonly OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        public readonly ReferenceName $referenceName,
        public readonly NodeReferencesToWrite $references,
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The content stream in which the create operation is to be performed
     * @param NodeAggregateId $sourceNodeAggregateId The identifier of the node aggregate to set references
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint The dimension space for which the references should be set
     * @param ReferenceName $referenceName Name of the reference to set
     * @param NodeReferencesToWrite $references Unserialized reference(s) to set
     */
    public static function create(ContentStreamId $contentStreamId, NodeAggregateId $sourceNodeAggregateId, OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint, ReferenceName $referenceName, NodeReferencesToWrite $references): self
    {
        return new self($contentStreamId, $sourceNodeAggregateId, $sourceOriginDimensionSpacePoint, $referenceName, $references);
    }
}
