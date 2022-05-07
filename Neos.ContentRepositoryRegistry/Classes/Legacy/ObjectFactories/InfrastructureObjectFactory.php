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

use Neos\ContentRepository\Infrastructure\Projection\ProcessedEventsAwareProjectorCollection;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepositoryRegistry\Legacy\HardcodedEventPublisherFactory;
use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\EventSourcing\EventPublisher\DeferEventPublisher;
use Symfony\Component\Serializer\Serializer;

final class InfrastructureObjectFactory
{
    public function __construct(
        private readonly ProcessedEventsAwareProjectorCollection $processedEventsAwareProjectorCollection,
        private readonly DeferEventPublisher $eventPublisher,

        private readonly ContentGraphInterface $contentGraph,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly HardcodedEventPublisherFactory $hardcodedEventPublisherFactory,
    )
    {
    }

    public function buildRuntimeBlocker(): RuntimeBlocker
    {
        return new RuntimeBlocker($this->eventPublisher, $this->hardcodedEventPublisherFactory->getMappings(), $this->processedEventsAwareProjectorCollection);
    }

    public function buildReadSideMemoryCacheManager(): ReadSideMemoryCacheManager
    {
        return new ReadSideMemoryCacheManager(
            $this->contentGraph,
            $this->workspaceFinder
        );
    }
}
