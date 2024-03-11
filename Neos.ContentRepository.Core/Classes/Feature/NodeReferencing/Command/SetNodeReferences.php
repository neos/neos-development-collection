<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Create a named reference from source to one or multiple destination nodes.
 *
 * The previously set references will be replaced by this command and not merged.
 *
 * Internally, this object is converted into a {@see SetSerializedNodeReferences} command, which is
 * then processed and stored.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class SetNodeReferences implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $sourceNodeAggregateId The identifier of the node aggregate to set references
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint The dimension space for which the references should be set
     * @param ReferenceName $referenceName Name of the reference to set
     * @param NodeReferencesToWrite $references Unserialized reference(s) to set
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $sourceNodeAggregateId,
        public OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        public ReferenceName $referenceName,
        public NodeReferencesToWrite $references,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the create operation is to be performed
     * @param NodeAggregateId $sourceNodeAggregateId The identifier of the node aggregate to set references
     * @param OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint The dimension space for which the references should be set
     * @param ReferenceName $referenceName Name of the reference to set
     * @param NodeReferencesToWrite $references Unserialized reference(s) to set
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $sourceNodeAggregateId, OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint, ReferenceName $referenceName, NodeReferencesToWrite $references): self
    {
        return new self($workspaceName, $sourceNodeAggregateId, $sourceOriginDimensionSpacePoint, $referenceName, $references);
    }
}
