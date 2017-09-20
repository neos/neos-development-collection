<?php

namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\EventInterface;

/**
 * RootNodeWasCreated
 */
final class RootNodeWasCreated implements EventInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * RootNodeWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeIdentifier
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeIdentifier, UserIdentifier $initiatingUserIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
