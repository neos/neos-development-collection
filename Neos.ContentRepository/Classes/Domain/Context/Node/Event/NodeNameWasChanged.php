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
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\EventSourcing\Event\EventInterface;

final class NodeNameWasChanged implements EventInterface, CopyableAcrossContentStreamsInterface
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
     * @var NodeName
     */
    private $newNodeName;

    /**
     * NodeNameWasChanged constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeName $newNodeName
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        NodeName $newNodeName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->newNodeName = $newNodeName;
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
     * @return NodeName
     */
    public function getNewNodeName(): NodeName
    {
        return $this->newNodeName;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new NodeNameWasChanged(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->newNodeName
        );
    }
}
