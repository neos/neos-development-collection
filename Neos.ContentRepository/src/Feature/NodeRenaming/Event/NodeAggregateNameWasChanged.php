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

namespace Neos\ContentRepository\Feature\NodeRenaming\Event;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateNameWasChanged implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeName $newNodeName,
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
            $this->newNodeName,
            $this->initiatingUserIdentifier
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($values['nodeAggregateIdentifier']),
            NodeName::fromString($values['newNodeName']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'newNodeName' => $this->newNodeName,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }
}
