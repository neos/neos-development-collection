<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface as T;
use Neos\EventStore\Model\EventEnvelope;

/**
 * An immutable set of Content Repository hooks ({@see ContentRepositoryHookInterface}
 *
 * @implements \IteratorAggregate<ContentRepositoryHookInterface>
 * @internal
 */
final class ContentRepositoryHooks implements \IteratorAggregate
{
    /**
     * @var array<ContentRepositoryHookInterface>
     */
    private array $hooks;

    private function __construct(ContentRepositoryHookInterface ...$hooks)
    {
        $this->hooks = $hooks;
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param array<ContentRepositoryHookInterface> $hooks
     * @return self
     */
    public static function fromArray(array $hooks): self
    {
        return new self(...array_values($hooks));
    }

    public function dispatchBeforeCatchUp(): void
    {
        foreach ($this->hooks as $hook) {
            $hook->onBeforeCatchUp();
        }
    }

    public function dispatchBeforeEvent(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        foreach ($this->hooks as $hook) {
            $hook->onBeforeEvent($event, $eventEnvelope);
        }
    }

    public function dispatchAfterEvent(EventInterface $event, EventEnvelope $eventEnvelope)
    {
        foreach ($this->hooks as $hook) {
            $hook->onAfterEvent($event, $eventEnvelope);
        }
    }

    public function dispatchAfterCatchup(): void
    {
        foreach ($this->hooks as $hook) {
            $hook->onAfterCatchUp();
        }
    }

    /**
     * @return \Traversable<ContentRepositoryHookInterface>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->hooks;
    }
}
