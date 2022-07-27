<?php

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\EventStore\EventInterface;

class DelegatingCatchUpHandler implements CatchUpHandlerInterface
{

    /**
     * @var CatchUpHandlerInterface[]
     */
    private array $catchUpHandlers;

    public function __construct(
        CatchUpHandlerInterface... $catchUpHandlers
    )
    {
        $this->catchUpHandlers = $catchUpHandlers;
    }

    public function onBeforeCatchUp(): void
    {
        foreach ($this->catchUpHandlers as $catchUpHandler) {
            $catchUpHandler->onBeforeCatchUp();
        }
    }

    public function onBeforeEvent(EventInterface $eventInstance): void
    {
        foreach ($this->catchUpHandlers as $catchUpHandler) {
            $catchUpHandler->onBeforeEvent($eventInstance);
        }
    }

    public function onAfterEvent(EventInterface $eventInstance): void
    {
        foreach ($this->catchUpHandlers as $catchUpHandler) {
            $catchUpHandler->onAfterEvent($eventInstance);
        }
    }

    public function onAfterCatchUp(): void
    {
        foreach ($this->catchUpHandlers as $catchUpHandler) {
            $catchUpHandler->onAfterCatchUp();
        }
    }
}
