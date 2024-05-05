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
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api commands are the write-API of the ContentRepository
 */
final readonly class RemoveNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the remove operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to remove
     * @param DimensionSpacePoint $coveredDimensionSpacePoint One of the dimension space points covered by the node aggregate in which the user intends to remove it
     * @param NodeVariantSelectionStrategy $nodeVariantSelectionStrategy The strategy the user chose to determine which specialization variants will also be removed
     * @param NodeAggregateId|null $removalAttachmentPoint Internal. It stores the document node id of the removed node, as that is what the UI needs later on for the change display. {@see self::withRemovalAttachmentPoint()}
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePoint $coveredDimensionSpacePoint,
        public NodeVariantSelectionStrategy $nodeVariantSelectionStrategy,
        public ?NodeAggregateId $removalAttachmentPoint
    ) {
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

    /**
     * @param array<string,mixed> $array
     */
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
     * This adds usually the NodeAggregateId of the parent document node of the deleted node.
     * It is needed for instance in the Neos UI for the following scenario:
     * - when removing a node, you still need to be able to publish the removal.
     * - For this to work, the Neos UI needs to know the id of the removed Node, **on the page where the removal happened**
     *   (so that the user can decide to publish a single page INCLUDING the removal on the page)
     * - Because this command will *remove* the edge,
     *   we cannot know the position in the tree after doing the removal anymore.
     *
     * @param NodeAggregateId $removalAttachmentPoint
     * @internal
     */
    public function withRemovalAttachmentPoint(NodeAggregateId $removalAttachmentPoint): self
    {
        return new self($this->workspaceName, $this->nodeAggregateId, $this->coveredDimensionSpacePoint, $this->nodeVariantSelectionStrategy, $removalAttachmentPoint);
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
                && $this->coveredDimensionSpacePoint === $nodeIdToPublish->dimensionSpacePoint
        );
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->removalAttachmentPoint,
        );
    }
}
