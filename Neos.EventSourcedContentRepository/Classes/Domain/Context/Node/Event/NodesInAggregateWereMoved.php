<?php
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

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\ReferencePosition;
use Neos\EventSourcing\Event\EventInterface;

/**
 * Nodes in aggregate were moved after, into or before another node event
 */
final class NodesInAggregateWereMoved implements EventInterface, CopyableAcrossContentStreamsInterface
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
     * Array from node identifier (string) to reference node identifier (string)
     *
     * @var array
     */
    private $nodesToReferenceNodes;

    /**
     * NodesInAggregateWereMoved constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param ReferencePosition $referencePosition
     * @param NodeAggregateIdentifier $referenceNodeAggregateIdentifier
     * @param array $nodesToReferenceNodes
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ReferencePosition $referencePosition,
        NodeAggregateIdentifier $referenceNodeAggregateIdentifier,
        array $nodesToReferenceNodes
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->referencePosition = $referencePosition;
        $this->referenceNodeAggregateIdentifier = $referenceNodeAggregateIdentifier;
        $this->nodesToReferenceNodes = $nodesToReferenceNodes;
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

    /**
     * @return array
     */
    public function getNodesToReferenceNodes(): array
    {
        return $this->nodesToReferenceNodes;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodesInAggregateWereMoved
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new NodesInAggregateWereMoved(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->referencePosition,
            $this->referenceNodeAggregateIdentifier,
            $this->nodesToReferenceNodes
        );
    }
}
