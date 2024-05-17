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

namespace Neos\ContentRepository\Core\Feature\NodeVariation\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\PublishableInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class NodePeerVariantWasCreated implements
    EventInterface,
    PublishableInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $sourceOrigin,
        public OriginDimensionSpacePoint $peerOrigin,
        public InterdimensionalSiblings $peerSucceedingSiblings,
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function createCopyForContentStream(WorkspaceName $targetWorkspaceName, ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->sourceOrigin,
            $this->peerOrigin,
            $this->peerSucceedingSiblings,
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($values['sourceOrigin']),
            OriginDimensionSpacePoint::fromArray($values['peerOrigin']),
            array_key_exists('peerSucceedingSiblings', $values)
                ? InterdimensionalSiblings::fromArray($values['peerSucceedingSiblings'])
                : InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                    DimensionSpacePointSet::fromArray($values['peerCoverage']),
                ),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
