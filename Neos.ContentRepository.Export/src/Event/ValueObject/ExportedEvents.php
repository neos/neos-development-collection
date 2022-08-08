<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Event\ValueObject;

/**
 * @implements \IteratorAggregate<ExportedEvent>
 */
final class ExportedEvents implements \IteratorAggregate
{

    private function __construct(
        private readonly \Closure $generator,
    ) {
    }

    public static function fromIterable(iterable $events): self
    {
        return new self(function () use ($events) {
            yield from $events;
        });
    }

    public static function fromJsonl(string $jsonl): self
    {
        return new self(
            function() use ($jsonl) {
                foreach (explode("\n", $jsonl) as $json) {
                    if ($json === '') {
                        continue;
                    }
                    yield ExportedEvent::fromJson($json);
                }
            }
        );
    }

    public function toJsonl(): string
    {
        $result = '';
        foreach ($this as $event) {
            $result .= $event->toJson() . "\n";
        }
        return $result;
    }

    /**
     * @return \Traversable<ExportedEvent>
     */
    public function getIterator(): \Traversable
    {
        return ($this->generator)();
    }
}
