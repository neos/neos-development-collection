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

namespace Neos\ContentRepository\Factory;


use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Log\ThrowableStorageInterface;

final class ProjectionFactoryDependencies
{
    public function __construct(
        public readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        public readonly EventStoreInterface $eventStore,
        public readonly EventNormalizer $eventNormalizer,
        public readonly NodeTypeManager $nodeTypeManager,
        public readonly ContentDimensionZookeeper $contentDimensionZookeeper, // TODO: check whether this is actually specified from outside
        public readonly InterDimensionalVariationGraph $interDimensionalVariationGraph, // TODO: check whether this is actually specified from outside
        public readonly ThrowableStorageInterface $throwableStorage, // TODO
        public readonly PropertyConverter $propertyConverter,
    )
    {
    }
}
