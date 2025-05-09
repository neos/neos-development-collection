<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The "Remove node aggregate" command
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class RemoveNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @deprecated with Neos 9 Beta 19. Must not be specified any longer. Might get removed at any point. Only part of the command to handle rebasing legacy events.
     */
    public ?NodeAggregateId $removalAttachmentPoint;

    /**
     * @param WorkspaceName $workspaceName The workspace in which the remove operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to remove
     * @param DimensionSpacePoint $coveredDimensionSpacePoint One of the dimension space points covered by the node aggregate in which the user intends to remove it
     * @param NodeVariantSelectionStrategy $nodeVariantSelectionStrategy The strategy the user chose to determine which specialization variants will also be removed
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePoint $coveredDimensionSpacePoint,
        public NodeVariantSelectionStrategy $nodeVariantSelectionStrategy,
        ?NodeAggregateId $removalAttachmentPoint
    ) {
        $this->removalAttachmentPoint = $removalAttachmentPoint;
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the remove operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to remove
     * @param DimensionSpacePoint $coveredDimensionSpacePoint One of the dimension space points covered by the node aggregate in which the user intends to remove it
     * @param NodeVariantSelectionStrategy $nodeVariantSelectionStrategy The strategy the user chose to determine which specialization variants will also be removed
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint, NodeVariantSelectionStrategy $nodeVariantSelectionStrategy): self
    {
        return new self($workspaceName, $nodeAggregateId, $coveredDimensionSpacePoint, $nodeVariantSelectionStrategy, null);
    }

    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
            NodeVariantSelectionStrategy::from($array['nodeVariantSelectionStrategy']),
            isset($array['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($array['removalAttachmentPoint'])
                : null
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->removalAttachmentPoint
        );
    }
}
