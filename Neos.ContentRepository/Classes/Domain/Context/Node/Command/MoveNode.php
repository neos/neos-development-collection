<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ReferencePosition;

/**
 * Move node command
 *
 * Moves a node before, into or after another node.
 *
 * This is only allowed if the node type of the node allows a single move and the node would be
 */
final class MoveNode
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
     * @var ReferencePosition
     */
    private $referencePosition;

    /**
     * @var NodeIdentifier
     */
    private $referenceNodeIdentifier;

    /**
     * MoveNode constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param ReferencePosition $referencePosition
     * @param NodeIdentifier $referenceNodeIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        ReferencePosition $referencePosition,
        NodeIdentifier $referenceNodeIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->referencePosition = $referencePosition;
        $this->referenceNodeIdentifier = $referenceNodeIdentifier;
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
     * @return ReferencePosition
     */
    public function getReferencePosition(): ReferencePosition
    {
        return $this->referencePosition;
    }

    /**
     * @return NodeIdentifier
     */
    public function getReferenceNodeIdentifier(): NodeIdentifier
    {
        return $this->referenceNodeIdentifier;
    }

}
