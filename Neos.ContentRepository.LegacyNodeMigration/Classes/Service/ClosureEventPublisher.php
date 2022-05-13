<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Service;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventPublisher\EventPublisherInterface;

final class ClosureEventPublisher implements EventPublisherInterface
{
    /**
     * @var \Closure
     */
    private $callback;

    public function setClosure(\Closure $callback): void
    {
        $this->callback = $callback;
    }

    public function publish(DomainEvents $events): void
    {
        if ($this->callback !== null) {
            call_user_func($this->callback, $events);
        }
    }
}
