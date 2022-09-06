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

namespace Neos\ContentRepository\Core\Feature\NodeModification\Event;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * When a node property is changed, this event is triggered.
 *
 * The projectors need to MERGE all the SerializedPropertyValues in these events (per node)
 * to get an up to date view of all the properties of a node.
 *
 * NOTE: if a value is set to NULL in SerializedPropertyValues, this means the key should be unset,
 * because we treat NULL and "not set" the same from an API perspective.
 *
 * @api events are the persistence-API of the content repository
 */
final class NodePropertiesWereSet implements EventInterface, PublishableToOtherContentStreamsInterface, EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(public readonly ContentStreamId $contentStreamId, public readonly NodeAggregateId $nodeAggregateId, public readonly OriginDimensionSpacePoint $originDimensionSpacePoint, public readonly SerializedPropertyValues $propertyValues,) {}

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self($targetContentStreamId, $this->nodeAggregateId, $this->originDimensionSpacePoint, $this->propertyValues);
    }

    public function mergeProperties(self $other): self
    {
        return new self($this->contentStreamId, $this->nodeAggregateId, $this->originDimensionSpacePoint, $this->propertyValues->merge($other->propertyValues));
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(ContentStreamId::fromString($values['contentStreamId']), NodeAggregateId::fromString($values['nodeAggregateId']), OriginDimensionSpacePoint::fromArray($values['originDimensionSpacePoint']), SerializedPropertyValues::fromArray($values['propertyValues']),);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
