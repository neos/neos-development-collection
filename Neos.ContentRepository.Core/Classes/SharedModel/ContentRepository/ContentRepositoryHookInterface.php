<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * This is a low-level API that allows you can hook into the Content Repository event handling
 *
 * To register such a Content Repository hook, create a corresponding {@see ContentRepositoryHookFactoryInterface}
 * and pass it to {@see ProjectionFactoryInterface::build()}.
 *
 * @api
 */
interface ContentRepositoryHookInterface
{
    /**
     * This hook is called at the beginning of {@see ContentRepository::catchUpProjections()};
     *
     * @return void
     */
    public function onBeforeCatchUp(): void;

    /**
     * This hook is called for every event during the catchup process, **before** the projections are updated.
     */
    public function onBeforeEvent(EventInterface $event, EventEnvelope $eventEnvelope): void;

    /**
     * This hook is called for every event during the catchup process, **after** the projections are updated
     */
    public function onAfterEvent(EventInterface $event, EventEnvelope $eventEnvelope): void;

    /**
     * This hook is called at the END of {@see ProjectionInterface::catchUpProjections()}, directly
     * before exiting the method.
     */
    public function onAfterCatchUp(): void;
}
