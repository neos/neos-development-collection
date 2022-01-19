<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\Flow\Annotations as Flow;

/**
 * Helper trait for the GraphProjector implementing callbacks for beforeInvoke and afterInvoke.
 *
 * @Flow\Scope("singleton")
 */
trait ProjectorEventHandlerTrait
{

    /**
     * @var array<callable> handlers to be executed in beforeInvoke - called with ({@see EventEnvelope}, bool $doingFullReplayOfProjection).
     */
    private array $beforeInvokeHandlers = [];

    /**
     * @var array<callable> handlers to be executed in afterInvoke - called with ({@see EventEnvelope}, bool $doingFullReplayOfProjection).
     */
    private array $afterInvokeHandlers = [];

    /**
     * @param callable $handler ({@see EventEnvelope} $event, bool $doingFullReplayOfProjection)
     * @return void
     */
    public function onBeforeInvoke(callable $handler): void {
        $this->beforeInvokeHandlers[] = $handler;
    }

    /**
     * @param callable $handler ({@see EventEnvelope} $event, bool $doingFullReplayOfProjection)
     * @return void
     */
    public function onAfterInvoke(callable $handler): void {
        $this->afterInvokeHandlers[] = $handler;
    }

    /**
     * Call this method in beforeInvoke()
     *
     * @param EventEnvelope $event
     * @return void
     */
    protected function triggerBeforeInvokeHandlers(EventEnvelope $event, bool $doingFullReplayOfProjection): void
    {
        foreach ($this->beforeInvokeHandlers as $handler) {
            $handler($event, $doingFullReplayOfProjection);
        }
    }

    /**
     * Call this method in afterInvoke()
     *
     * @param EventEnvelope $event
     * @return void
     */
    protected function triggerAfterInvokeHandlers(EventEnvelope $event, bool $doingFullReplayOfProjection): void
    {
        foreach ($this->afterInvokeHandlers as $handler) {
            $handler($event, $doingFullReplayOfProjection);
        }
    }
}
