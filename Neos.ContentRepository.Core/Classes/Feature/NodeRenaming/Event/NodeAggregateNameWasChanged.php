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

namespace Neos\ContentRepository\Core\Feature\NodeRenaming\Event;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateNameWasChanged implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeName $newNodeName,
        public readonly UserId $initiatingUserId
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

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->newNodeName,
            $this->initiatingUserId
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            NodeName::fromString($values['newNodeName']),
            UserId::fromString($values['initiatingUserId'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'newNodeName' => $this->newNodeName,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }
}
