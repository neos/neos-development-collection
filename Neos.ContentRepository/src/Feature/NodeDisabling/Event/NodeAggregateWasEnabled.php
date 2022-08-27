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

namespace Neos\ContentRepository\Feature\NodeDisabling\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\EventStore\EventInterface;

/**
 * A node aggregate was enabled
 *
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateWasEnabled implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly DimensionSpacePointSet $affectedDimensionSpacePoints,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->affectedDimensionSpacePoints,
            $this->initiatingUserIdentifier
        );
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($values['nodeAggregateIdentifier']),
            DimensionSpacePointSet::fromArray($values['affectedDimensionSpacePoints']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'affectedDimensionSpacePoints' => $this->affectedDimensionSpacePoints,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }
}
