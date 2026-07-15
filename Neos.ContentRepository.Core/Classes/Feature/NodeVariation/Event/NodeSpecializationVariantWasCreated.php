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
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A node specialization variant was created
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class NodeSpecializationVariantWasCreated implements
    EventInterface,
    PublishableToWorkspaceInterface,
    EmbedsContentStreamId,
    EmbedsNodeAggregateId,
    EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $sourceOrigin,
        public OriginDimensionSpacePoint $specializationOrigin,
        public InterdimensionalSiblings $specializationSiblings,
        public ?NodeAggregateId $parentNodeAggregateId,
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

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            workspaceName: $targetWorkspaceName,
            contentStreamId: $contentStreamId,
            nodeAggregateId: $this->nodeAggregateId,
            sourceOrigin: $this->sourceOrigin,
            specializationOrigin: $this->specializationOrigin,
            specializationSiblings: $this->specializationSiblings,
            parentNodeAggregateId: $this->parentNodeAggregateId,
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            workspaceName: WorkspaceName::fromString($values['workspaceName']),
            contentStreamId: ContentStreamId::fromString($values['contentStreamId']),
            nodeAggregateId: NodeAggregateId::fromString($values['nodeAggregateId']),
            sourceOrigin: OriginDimensionSpacePoint::fromArray($values['sourceOrigin']),
            specializationOrigin: OriginDimensionSpacePoint::fromArray($values['specializationOrigin']),
            specializationSiblings: array_key_exists('specializationSiblings', $values)
                ? InterdimensionalSiblings::fromArray($values['specializationSiblings'])
                : InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                    DimensionSpacePointSet::fromArray($values['specializationCoverage'])
                ),
            parentNodeAggregateId: is_string($parentNodeAggregateId = ($values['parentNodeAggregateId'] ?? null))
                ? NodeAggregateId::fromString($parentNodeAggregateId)
                : null,
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
