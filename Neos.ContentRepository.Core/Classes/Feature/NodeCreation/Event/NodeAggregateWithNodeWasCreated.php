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

namespace Neos\ContentRepository\Core\Feature\NodeCreation\Event;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * A node aggregate with its initial node was created
 *
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateWithNodeWasCreated implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $nodeTypeName,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly DimensionSpacePointSet $coveredDimensionSpacePoints,
        public readonly NodeAggregateId $parentNodeAggregateId,
        public readonly ?NodeName $nodeName,
        public readonly SerializedPropertyValues $initialPropertyValues,
        public readonly NodeAggregateClassification $nodeAggregateClassification,
        public readonly ?NodeAggregateId $succeedingNodeAggregateId = null
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

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->coveredDimensionSpacePoints,
            $this->parentNodeAggregateId,
            $this->nodeName,
            $this->initialPropertyValues,
            $this->nodeAggregateClassification,
            $this->succeedingNodeAggregateId
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            NodeTypeName::fromString($values['nodeTypeName']),
            OriginDimensionSpacePoint::fromArray($values['originDimensionSpacePoint']),
            DimensionSpacePointSet::fromArray($values['coveredDimensionSpacePoints']),
            NodeAggregateId::fromString($values['parentNodeAggregateId']),
            isset($values['nodeName']) ? NodeName::fromString($values['nodeName']) : null,
            SerializedPropertyValues::fromArray($values['initialPropertyValues']),
            NodeAggregateClassification::from($values['nodeAggregateClassification']),
            isset($values['succeedingNodeAggregateId'])
                ? NodeAggregateId::fromString($values['succeedingNodeAggregateId'])
                : null,
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
