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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class DimensionSpaceCommandHandlerObjectFactory
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        private readonly RuntimeBlocker $runtimeBlocker,
        private readonly ContentGraphInterface $contentGraph,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph
    )
    {
    }


    public function buildDimensionSpaceCommandHandler(): DimensionSpaceCommandHandler
    {
        return new DimensionSpaceCommandHandler(
            $this->eventStore,
            $this->readSideMemoryCacheManager,
            $this->contentGraph,
            $this->contentDimensionZookeeper,
            $this->interDimensionalVariationGraph,
            $this->runtimeBlocker
        );
    }
}
