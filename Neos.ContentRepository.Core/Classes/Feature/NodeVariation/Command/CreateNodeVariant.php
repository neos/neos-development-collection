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

namespace Neos\ContentRepository\Core\Feature\NodeVariation\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\User\UserId;

/**
 * Create a variant of a node in a content stream
 *
 * Copy a node to another dimension space point respecting further variation mechanisms
 *
 * @api commands are the write-API of the ContentRepository
 */
final class CreateNodeVariant implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly OriginDimensionSpacePoint $sourceOrigin,
        public readonly OriginDimensionSpacePoint $targetOrigin,
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
            OriginDimensionSpacePoint::fromArray($array['sourceOrigin']),
            OriginDimensionSpacePoint::fromArray($array['targetOrigin']),
            UserId::fromString($array['initiatingUserId'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'sourceOrigin' => $this->sourceOrigin,
            'targetOrigin' => $this->targetOrigin,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return $this->contentStreamId->equals($nodeIdToPublish->contentStreamId)
            && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
            && $this->targetOrigin->equals($nodeIdToPublish->dimensionSpacePoint);
    }

    public function createCopyForContentStream(ContentStreamId $target): CommandInterface
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->sourceOrigin,
            $this->targetOrigin,
            $this->initiatingUserId
        );
    }
}
