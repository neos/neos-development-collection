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
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ContentStreamCommandHandlerObjectFactory
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly ContentStreamRepository $contentStreamRepository,
        private readonly ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        private readonly RuntimeBlocker $runtimeBlocker
    )
    {
    }


    public function buildContentStreamCommandHandler(): ContentStreamCommandHandler
    {
        return new ContentStreamCommandHandler(
            $this->contentStreamRepository,
            $this->eventStore,
            $this->readSideMemoryCacheManager,
            $this->runtimeBlocker
        );
    }
}
