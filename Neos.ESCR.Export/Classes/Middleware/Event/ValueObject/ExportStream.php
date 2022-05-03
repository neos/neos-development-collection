<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event\ValueObject;

use Neos\EventSourcing\EventStore\EventStream;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<ExportedEvent>
 */
final class ExportStream implements \IteratorAggregate
{

    private function __construct(
        private readonly EventStream $eventStream,
        private readonly Attributes $attributes
    ) {
    }

    public static function fromEventStreamAndAttributes(EventStream $eventStream, Attributes $attributes): self
    {
        return new self($eventStream, $attributes);
    }

    /**
     * @return \Traversable<ExportedEvent>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->eventStream as $eventEnvelope) {
            yield ExportedEvent::fromRawEvent($eventEnvelope->getRawEvent(), $this->attributes);
        }
    }
}
