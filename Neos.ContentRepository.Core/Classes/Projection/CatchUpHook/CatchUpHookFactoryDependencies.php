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

namespace Neos\ContentRepository\Core\Projection\CatchUpHook;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @template-covariant T of ProjectionStateInterface
 *
 * @api provides available dependencies for implementing a catch-up hook.
 */
final readonly class CatchUpHookFactoryDependencies
{
    /**
     * @param ContentRepositoryId $contentRepositoryId the content repository the catchup was registered in
     * @param ProjectionStateInterface&T $projectionState the state of the projection the catchup was registered to (Its only safe to access this projections state)
     */
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public ProjectionStateInterface $projectionState,
        public NodeTypeManager $nodeTypeManager,
        public ContentDimensionSourceInterface $contentDimensionSource,
        public InterDimensionalVariationGraph $variationGraph
    ) {
    }

    /**
     * @template U of ProjectionStateInterface
     * @param ProjectionStateInterface&U $projectionState
     * @return CatchUpHookFactoryDependencies<U>
     * @internal
     */
    public static function create(
        ContentRepositoryId $contentRepositoryId,
        ProjectionStateInterface $projectionState,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionSourceInterface $contentDimensionSource,
        InterDimensionalVariationGraph $variationGraph
    ): self {
        return new self(
            $contentRepositoryId,
            $projectionState,
            $nodeTypeManager,
            $contentDimensionSource,
            $variationGraph
        );
    }
}
