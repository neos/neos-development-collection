<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class NodeAggregateNameWasChanged implements DomainEventInterface, PublishableToOtherContentStreamsInterface, EmbedsContentStreamAndNodeAggregateIdentifier
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    private NodeName $newNodeName;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeName $newNodeName,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeName = $newNodeName;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNewNodeName(): NodeName
    {
        return $this->newNodeName;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier)
    {
        return new NodeAggregateNameWasChanged(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->newNodeName,
            $this->initiatingUserIdentifier
        );
    }
}
