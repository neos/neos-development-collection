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

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\EventStore\EventStoreInterface;

/**
 * @api because it is used inside the ProjectionsFactory
 */
final readonly class ProjectionFactoryDependencies
{
    public function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public EventStoreInterface $eventStore,
        public EventNormalizer $eventNormalizer,
        public NodeTypeManager $nodeTypeManager,
        public ContentDimensionSourceInterface $contentDimensionSource,
        public ContentDimensionZookeeper $contentDimensionZookeeper,
        public InterDimensionalVariationGraph $interDimensionalVariationGraph,
        public PropertyConverter $propertyConverter,
    ) {
    }
}
