<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ReferencePosition;

/**
 * Move nodes in aggregate command
 *
 * Moves all nodes in the given `nodeAggregateIdentifier` to the `referencePosition` of the
 * `referenceNodeAggregateIdentifier`.
 */
final class MoveNodesInAggregate
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var ReferencePosition
     */
    private $referencePosition;

    /**
     * @var NodeAggregateIdentifier
     */
    private $referenceNodeAggregateIdentifier;

    /**
     * MoveNodesInAggregate constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param ReferencePosition $referencePosition
     * @param NodeAggregateIdentifier $referenceNodeAggregateIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ReferencePosition $referencePosition,
        NodeAggregateIdentifier $referenceNodeAggregateIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->referencePosition = $referencePosition;
        $this->referenceNodeAggregateIdentifier = $referenceNodeAggregateIdentifier;
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
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return ReferencePosition
     */
    public function getReferencePosition(): ReferencePosition
    {
        return $this->referencePosition;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getReferenceNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->referenceNodeAggregateIdentifier;
    }

}
