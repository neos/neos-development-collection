<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Module\Administration;

use Neos\ContentRepository\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\Neos\Presentation\Dimensions\VisualIntraDimensionalVariationGraph;
use Neos\Neos\Presentation\Dimensions\VisualInterDimensionalVariationGraph;

/**
 * @deprecated really un-nice :D
 */
class DimensionControllerInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        protected ContentDimensionSourceInterface $contentDimensionSource,
        protected InterDimensionalVariationGraph $interDimensionalVariationGraph
    ) {
    }

    public function loadGraph(
        string $type,
        string $dimensionSpacePointHash = null
    ): VisualIntraDimensionalVariationGraph|VisualInterDimensionalVariationGraph|null {
        $dimensionSpacePoint = $dimensionSpacePointHash
            ? $this->interDimensionalVariationGraph->getDimensionSpacePoints()[$dimensionSpacePointHash]
            : null;
        return match ($type) {
            'intraDimension' => VisualIntraDimensionalVariationGraph::fromContentDimensionSource(
                $this->contentDimensionSource
            ),
            'interDimension' => $dimensionSpacePoint
                ? VisualInterDimensionalVariationGraph::forInterDimensionalVariationSubgraph(
                    $this->interDimensionalVariationGraph,
                    $dimensionSpacePoint
                )
                : VisualInterDimensionalVariationGraph::forInterDimensionalVariationGraph(
                    $this->interDimensionalVariationGraph,
                    $this->contentDimensionSource
                ),
            default => null,
        };
    }
}
