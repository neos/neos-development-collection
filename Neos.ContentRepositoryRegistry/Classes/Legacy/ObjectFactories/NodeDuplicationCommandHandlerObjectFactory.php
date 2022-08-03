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
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
final class NodeDuplicationCommandHandlerObjectFactory
{
    public function __construct(
        private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler,
        private readonly ContentGraphInterface $contentGraph,
        private readonly ContentStreamRepository $contentStreamRepository,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        private readonly NodeAggregateEventPublisher $nodeAggregateEventPublisher,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly RuntimeBlocker $runtimeBlocker
    )
    {
    }


    public function buildNodeDuplicationCommandHandler(): NodeDuplicationCommandHandler
    {
        return new NodeDuplicationCommandHandler(
            $this->nodeAggregateCommandHandler,
            $this->contentGraph,
            $this->contentStreamRepository,
            $this->nodeTypeManager,
            $this->readSideMemoryCacheManager,
            $this->nodeAggregateEventPublisher,
            $this->contentDimensionZookeeper,
            $this->interDimensionalVariationGraph,
            $this->runtimeBlocker
        );
    }
}
