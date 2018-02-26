<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
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
     * @var NodeTypeName
     */
    protected $nodeTypeName;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * CreateRootNode constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier, NodeTypeName $nodeTypeName, UserIdentifier $initiatingUserIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeTypeName = $nodeTypeName;
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
     * Getter for NodeTypeName
     *
     * @return NodeTypeName
     */
    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    /**
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
