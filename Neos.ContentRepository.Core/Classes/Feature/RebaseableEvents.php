<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

/**
 * @internal
 * @implements \IteratorAggregate<RebaseableEvent>
 */
class RebaseableEvents implements \IteratorAggregate
{
    /**
     * @var array<RebaseableEvent>
     */
    private array $items;

    public function __construct(
        RebaseableEvent ...$items
    ) {
        $this->items = $items;
    }

    /**
     * @return array{RebaseableEvents,RebaseableEvents}
     */
    public function separateMatchingAndRemainingEvents(
        NodeAggregateIds $nodeIdsToMatch
    ): array {
        $matchingEvents = [];
        $remainingEvents = [];
        foreach ($this->items as $extractedEvent) {
            if ($extractedEvent->event instanceof EmbedsNodeAggregateId && $nodeIdsToMatch->contain($extractedEvent->event->getNodeAggregateId())) {
                $matchingEvents[] = $extractedEvent;
            } else {
                $remainingEvents[] = $extractedEvent;
            }
        }
        return [
            new RebaseableEvents(...$matchingEvents),
            new RebaseableEvents(...$remainingEvents)
        ];
    }

    public function withAppended(RebaseableEvent $event): self
    {
        return new self(...[...$this->items, $event]);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
