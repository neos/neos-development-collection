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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * Set property values for a given node (internal implementation).
 *
 * The property values contain the serialized types already, and include type information.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class SetSerializedNodeReferences implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $sourceNodeAggregateId,
        public readonly OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        public readonly ReferenceName $referenceName,
        public readonly SerializedNodeReferences $references,
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
            NodeAggregateId::fromString($array['sourceNodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['sourceOriginDimensionSpacePoint']),
            ReferenceName::fromString($array['referenceName']),
            SerializedNodeReferences::fromArray($array['references']),
            UserId::fromString($array['initiatingUserId'])
        );
    }

    /**
     * @internal
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'sourceNodeAggregateId' => $this->sourceNodeAggregateId,
            'sourceOriginDimensionSpacePoint' => $this->sourceOriginDimensionSpacePoint,
            'referenceName' => $this->referenceName,
            'references' => $this->references,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->sourceNodeAggregateId,
            $this->sourceOriginDimensionSpacePoint,
            $this->referenceName,
            $this->references,
            $this->initiatingUserId
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->contentStreamId === $nodeIdToPublish->contentStreamId
                && $this->sourceOriginDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
                && $this->sourceNodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }
}
