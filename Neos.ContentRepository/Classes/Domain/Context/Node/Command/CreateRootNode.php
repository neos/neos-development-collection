<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * CreateRootNode
 */
final class CreateRootNode
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
     * CreateRootNode constructor.
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
