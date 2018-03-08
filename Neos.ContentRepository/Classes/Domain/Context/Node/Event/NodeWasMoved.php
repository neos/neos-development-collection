<?php
namespace Neos\ContentRepository\Domain\Context\Node\Event;

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
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ReferencePosition;
use Neos\EventSourcing\Event\EventInterface;

/**
 * Node was moved after, into or before another node event
 */
final class NodeWasMoved implements EventInterface, CopyableAcrossContentStreamsInterface
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
     * NodeWasMoved constructor.
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

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodeWasMoved
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new NodeWasMoved(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->referencePosition,
            $this->referenceNodeIdentifier
        );
    }
}
