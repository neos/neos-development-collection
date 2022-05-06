<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class CommandHandlerObjectFactory
{
    public function __construct(
        protected readonly DbalClientInterface $dbalClient,
        protected readonly EventStore $eventStore
    ) {}

    public function buildContentStreamRepository()
    {
        return new ContentStreamRepository($this->eventStore);
    }

    public function buildNodeAggregateEventPublisher(): NodeAggregateEventPublisher
    {
        return new NodeAggregateEventPublisher(
            $this->eventStore
        );
    }
}
