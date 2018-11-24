<?php
namespace Neos\ContentRepository\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An interface for plugins for the NodeCommandController that dispatches events
 */
interface EventDispatchingNodeCommandControllerPluginInterface extends NodeCommandControllerPluginInterface
{

    /**
     * Entering a task. Event arguments are $taskDescription, $taskClosure (only executed if not in dry-run) and (optionally)  a $requiresConfirmation flag
     */
    const EVENT_TASK = 'task';

    /**
     * A notice is printed to the console
     */
    const EVENT_NOTICE = 'notice';

    /**
     * A warning is printed to the console but does not quit the execution
     */
    const EVENT_WARNING = 'warning';

    /**
     * An error is printed to the console and stops the execution with an error exit code
     */
    const EVENT_ERROR = 'error';

    /**
     * Attaches a new event handler
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param \Closure $callback a closure to be invoked when the corresponding event was triggered
     * @return void
     */
    public function on(string $eventIdentifier, \Closure $callback): void;
}
