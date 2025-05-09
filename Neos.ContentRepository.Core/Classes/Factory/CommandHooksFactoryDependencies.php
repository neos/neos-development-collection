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

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @api for implementers of custom {@see CommandHookInterface}s
 */
final readonly class CommandHooksFactoryDependencies
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public ContentGraphReadModelInterface $contentGraphReadModel,
        public NodeTypeManager $nodeTypeManager,
        public ContentDimensionSourceInterface $contentDimensionSource,
        public InterDimensionalVariationGraph $variationGraph
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        ContentRepositoryId $contentRepositoryId,
        ContentGraphReadModelInterface $contentGraphReadModel,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionSourceInterface $contentDimensionSource,
        InterDimensionalVariationGraph $variationGraph
    ): self {
        return new self(
            $contentRepositoryId,
            $contentGraphReadModel,
            $nodeTypeManager,
            $contentDimensionSource,
            $variationGraph
        );
    }
}
