<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

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
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\ReferencePosition;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Node was moved after, into or before another node event
 *
 * @Flow\Proxy(false)
 */
final class NodeWasMoved implements DomainEventInterface, CopyableAcrossContentStreamsInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
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
     * NodeWasMoved constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param ReferencePosition $referencePosition
     * @param NodeAggregateIdentifier $referenceNodeIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        ReferencePosition $referencePosition,
        NodeAggregateIdentifier $referenceNodeIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeIdentifier;
        $this->referencePosition = $referencePosition;
        $this->referenceNodeAggregateIdentifier = $referenceNodeIdentifier;
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

    /**
     * @param ContentStreamIdentifier $targetContentStreamIdentifier
     * @return NodeWasMoved
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier)
    {
        return new NodeWasMoved(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->referencePosition,
            $this->referenceNodeAggregateIdentifier
        );
    }
}
