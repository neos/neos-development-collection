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

namespace Neos\ContentRepository\Core\Feature\NodeModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;

/**
 * Set property values for a given node (internal implementation).
 *
 * The property values contain the serialized types already, and include type information.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class SetSerializedNodeProperties implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly SerializedPropertyValues $propertyValues,
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
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            SerializedPropertyValues::fromArray($array['propertyValues']),
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
            'nodeAggregateId' => $this->nodeAggregateId,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
            'propertyValues' => $this->propertyValues,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->originDimensionSpacePoint,
            $this->propertyValues,
            $this->initiatingUserId
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->contentStreamId === $nodeIdToPublish->contentStreamId
                && $this->originDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
                && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }
}
