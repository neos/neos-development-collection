<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Options
{

    private function __construct(
        public readonly EventStoreIdentifier $eventStoreIdentifier,
        public readonly ExportFilter $exportFilter,
        public readonly Attributes $attributes
    ) {}

    public static function forEventStore(EventStoreIdentifier $eventStoreIdentifier): self
    {
        return new self($eventStoreIdentifier, ExportFilter::default(), Attributes::default());
    }

    public function withFilter(ExportFilter $filter): self
    {
        return new self($this->eventStoreIdentifier, $filter, $this->attributes);
    }

    public function withAttributes(Attributes $attributes): self
    {
        return new self($this->eventStoreIdentifier, $this->exportFilter, $attributes);
    }
}
