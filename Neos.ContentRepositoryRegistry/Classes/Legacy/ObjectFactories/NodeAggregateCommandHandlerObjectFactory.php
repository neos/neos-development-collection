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
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
final class NodeAggregateCommandHandlerObjectFactory
{
    public function __construct(
        private readonly ContentStreamRepository $contentStreamRepository,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly ContentGraphInterface $contentGraph,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeAggregateEventPublisher $nodeEventPublisher,
        private readonly ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        private readonly RuntimeBlocker $runtimeBlocker,
        private readonly PropertyConverter $propertyConverter
    )
    {
    }

    public function buildNodeAggregateCommandHandler(): NodeAggregateCommandHandler
    {
        return new NodeAggregateCommandHandler(
            $this->contentStreamRepository,
            $this->nodeTypeManager,
            $this->contentDimensionZookeeper,
            $this->contentGraph,
            $this->interDimensionalVariationGraph,
            $this->nodeEventPublisher,
            $this->readSideMemoryCacheManager,
            $this->runtimeBlocker,
            $this->propertyConverter
        );
    }
}
