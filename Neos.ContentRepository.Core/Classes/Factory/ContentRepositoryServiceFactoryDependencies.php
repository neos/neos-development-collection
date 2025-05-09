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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\EventStore\EventStoreInterface;

/**
 * Implementation detail of {@see ContentRepositoryServiceFactoryInterface}
 *
 * @internal as dependency collection inside {@see ContentRepositoryServiceFactoryInterface}
 */
final readonly class ContentRepositoryServiceFactoryDependencies
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public EventStoreInterface $eventStore,
        public EventNormalizer $eventNormalizer,
        public NodeTypeManager $nodeTypeManager,
        public ContentDimensionSourceInterface $contentDimensionSource,
        public ContentDimensionZookeeper $contentDimensionZookeeper,
        public InterDimensionalVariationGraph $interDimensionalVariationGraph,
        public PropertyConverter $propertyConverter,
        public ContentRepository $contentRepository,
        public ContentGraphReadModelInterface $contentGraphReadModel,
        public SubscriptionEngine $subscriptionEngine,
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        ContentRepositoryId $contentRepositoryId,
        EventStoreInterface $eventStore,
        EventNormalizer $eventNormalizer,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionSourceInterface $contentDimensionSource,
        ContentDimensionZookeeper $contentDimensionZookeeper,
        InterDimensionalVariationGraph $interDimensionalVariationGraph,
        PropertyConverter $propertyConverter,
        ContentRepository $contentRepository,
        ContentGraphReadModelInterface $contentGraphReadModel,
        SubscriptionEngine $subscriptionEngine,
    ): self {
        return new self(
            $contentRepositoryId,
            $eventStore,
            $eventNormalizer,
            $nodeTypeManager,
            $contentDimensionSource,
            $contentDimensionZookeeper,
            $interDimensionalVariationGraph,
            $propertyConverter,
            $contentRepository,
            $contentGraphReadModel,
            $subscriptionEngine,
        );
    }
}
