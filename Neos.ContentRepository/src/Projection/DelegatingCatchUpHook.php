<?php

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\EventStore\EventInterface;

class DelegatingCatchUpHook implements CatchUpHookInterface
{
    /**
     * @var CatchUpHookInterface[]
     */
    private array $catchUpHooks;

    public function __construct(
        CatchUpHookInterface ...$catchUpHooks
    ) {
        $this->catchUpHooks = $catchUpHooks;
    }

    public function onBeforeCatchUp(): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onBeforeCatchUp();
        }
    }

    public function onBeforeEvent(EventInterface $eventInstance): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onBeforeEvent($eventInstance);
        }
    }

    public function onAfterEvent(EventInterface $eventInstance): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onAfterEvent($eventInstance);
        }
    }

    public function onBeforeBatchCompleted(): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onBeforeBatchCompleted();
        }
    }

    public function onAfterCatchUp(): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onAfterCatchUp();
        }
    }
}
