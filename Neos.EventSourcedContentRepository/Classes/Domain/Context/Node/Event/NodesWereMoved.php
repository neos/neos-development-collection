<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcing\Event\EventInterface;

/**
 * Nodes were moved in a content stream as defined in the node move mappings
 */
final class NodesWereMoved implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var array|NodeMoveMapping[]
     */
    private $nodeMoveMappings;


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param array|NodeMoveMapping[] $nodeMoveMappings
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        array $nodeMoveMappings
    ) {
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
     * @return array|NodeMoveMapping[]
     */
    public function getNodeMoveMappings(): array
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
