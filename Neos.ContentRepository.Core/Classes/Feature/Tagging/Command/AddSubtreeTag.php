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

namespace Neos\ContentRepository\Core\Feature\Tagging\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\Tagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * Add a {@see SubtreeTag} to a node aggregate and its descendants
 *
 * @api commands are the write-API of the ContentRepository
 */
final class AddSubtreeTag implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    /**
     * @param ContentStreamId $contentStreamId The content stream in which the tagging operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to tag
     * @param DimensionSpacePoint $coveredDimensionSpacePoint The covered dimension space point of the node aggregate in which the user intends to tag it
     * @param NodeVariantSelectionStrategy $nodeVariantSelectionStrategy The strategy the user chose to determine which specialization variants will also be tagged
     * @param SubtreeTag $tag The tag to add to the Subtree
     */
    private function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly DimensionSpacePoint $coveredDimensionSpacePoint,
        public readonly NodeVariantSelectionStrategy $nodeVariantSelectionStrategy,
        public readonly SubtreeTag $tag,
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The content stream in which the tagging operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The identifier of the node aggregate to tag
     * @param DimensionSpacePoint $coveredDimensionSpacePoint The covered dimension space point of the node aggregate in which the user intends to tag it
     * @param NodeVariantSelectionStrategy $nodeVariantSelectionStrategy The strategy the user chose to determine which specialization variants will also be tagged
     * @param SubtreeTag $tag The tag to add to the Subtree
     */
    public static function create(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint, NodeVariantSelectionStrategy $nodeVariantSelectionStrategy, SubtreeTag $tag): self
    {
        return new self($contentStreamId, $nodeAggregateId, $coveredDimensionSpacePoint, $nodeVariantSelectionStrategy, $tag);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
            NodeVariantSelectionStrategy::from($array['nodeVariantSelectionStrategy']),
            SubtreeTag::fromString($array['tag']),
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->tag,
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->contentStreamId === $nodeIdToPublish->contentStreamId
                && $this->coveredDimensionSpacePoint === $nodeIdToPublish->dimensionSpacePoint
                && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }
}
