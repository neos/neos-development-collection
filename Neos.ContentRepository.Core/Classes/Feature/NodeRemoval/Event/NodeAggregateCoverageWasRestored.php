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

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\RecursionMode;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class NodeAggregateCoverageWasRestored implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        /** The source dimension space point to restore the coverage from */
        public readonly DimensionSpacePoint $sourceDimensionSpacePoint,
        /** The affected dimension space points to restore the coverage to */
        public readonly DimensionSpacePointSet $affectedCoveredDimensionSpacePoints,
        /** The mode to determine which descendants to affect as well. {@see RecursionMode} */
        public readonly RecursionMode $recursionMode,
        public readonly UserId $initiatingUserId
    ) {
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            DimensionSpacePoint::fromArray($values['sourceDimensionSpacePoint']),
            DimensionSpacePointSet::fromArray($values['affectedCoveredDimensionSpacePoints']),
            RecursionMode::from($values['recursionMode']),
            UserId::fromString($values['initiatingUserId'])
        );
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->sourceDimensionSpacePoint,
            $this->affectedCoveredDimensionSpacePoints,
            $this->recursionMode,
            $this->initiatingUserId
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'sourceDimensionSpacePoint' => $this->sourceDimensionSpacePoint,
            'affectedCoveredDimensionSpacePoints' => $this->affectedCoveredDimensionSpacePoints,
            'recursionMode' => $this->recursionMode,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }
}
