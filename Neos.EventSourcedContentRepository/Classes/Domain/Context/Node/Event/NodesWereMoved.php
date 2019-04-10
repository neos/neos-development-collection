<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;

/**
 * Nodes of a node aggregate were moved in a content stream as defined in the node move mappings
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
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeMoveMappings
     */
    private $nodeMoveMappings;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeMoveMappings $nodeMoveMappings
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeMoveMappings = $nodeMoveMappings;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNodeMoveMappings(): NodeMoveMappings
    {
        return $this->nodeMoveMappings;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodesWereMoved
    {
        return new NodesWereMoved(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->nodeMoveMappings
        );
    }
}
