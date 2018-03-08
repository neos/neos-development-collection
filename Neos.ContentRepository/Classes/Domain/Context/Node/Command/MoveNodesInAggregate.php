<?php
namespace Neos\ContentRepository\Domain\Context\Node\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ReferencePosition;

/**
 * Move nodes in aggregate command
 *
 * Moves all nodes in the given `nodeAggregateIdentifier` to the `referencePosition` of the
 * `referenceNodeAggregateIdentifier`. Each node in the node aggregate needs a suitable
 * (visible in dimension space point) reference node in the referenced node aggregate.
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
