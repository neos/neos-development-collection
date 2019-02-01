<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMapping;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Nodes were moved in a content stream as defined in the node move mappings
 *
 * @Flow\Proxy(false)
 */
final class NodesWereMoved implements DomainEventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeMoveMappings
     */
    private $nodeMoveMappings;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeMoveMappings $nodeMoveMappings
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeMoveMappings $nodeMoveMappings) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeMoveMappings = $nodeMoveMappings;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeMoveMappings
     */
    public function getNodeMoveMappings(): NodeMoveMappings
    {
        return $this->nodeMoveMappings;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodesWereMoved
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodesWereMoved
    {
        return new NodesWereMoved(
            $targetContentStream,
            $this->nodeMoveMappings
        );
    }
}
