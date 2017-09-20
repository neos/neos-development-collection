<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;

/**
 * Create root node command
 *
 * A root node has no aggregate (and is colorless in the content graph).
 */
final class CreateRootNode
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
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
     * @param NodeIdentifier $nodeIdentifier New node identifier
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier, UserIdentifier $initiatingUserIdentifier)
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
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
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
