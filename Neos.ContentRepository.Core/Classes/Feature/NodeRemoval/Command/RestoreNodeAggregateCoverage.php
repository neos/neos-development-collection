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

namespace Neos\ContentRepository\Feature\NodeRemoval\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Feature\Common\RecursionMode;

/**
 * The command to restore coverage of a node aggregate.
 * If a specialization variant of a node is deleted, the node and its descendants are no longer available
 * in the variant's dimension space point.
 * With this command, the fallback mechanism can be restored for that node and its descendants,
 * i.e. the node will be available in the specialization dimension space point with fallback content.
 */
final class RestoreNodeAggregateCoverage implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        /** The dimension space point the node aggregate should cover again. */
        public readonly DimensionSpacePoint $dimensionSpacePointToCover,
        /** If set to true, also all specializations of the selected dimension space point will be restored */
        public readonly bool $withSpecializations,
        /** The mode to determine which descendants to affect as well. {@see RecursionMode} */
        public readonly RecursionMode $recursionMode,
        public readonly UserId $initiatingUserId
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePointToCover']),
            $array['withSpecializations'],
            RecursionMode::from($array['recursionMode']),
            UserId::fromString($array['initiatingUserId']),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamId,
            'nodeAggregateIdentifier' => $this->nodeAggregateId,
            'coveredDimensionSpacePoint' => $this->dimensionSpacePointToCover,
            'withSpecializations' => $this->withSpecializations,
            'recursionMode' => $this->recursionMode,
            'initiatingUserIdentifier' => $this->initiatingUserId
        ];
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->dimensionSpacePointToCover,
            $this->withSpecializations,
            $this->recursionMode,
            $this->initiatingUserId
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->contentStreamId === $nodeIdToPublish->contentStreamId
            && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
            && $this->dimensionSpacePointToCover === $nodeIdToPublish->dimensionSpacePoint
        );
    }
}
