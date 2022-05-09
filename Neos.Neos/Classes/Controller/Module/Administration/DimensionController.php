<?php
namespace Neos\Neos\Controller\Module\Administration;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Presentation\Dimensions\VisualIntraDimensionalVariationGraph;
use Neos\Neos\Presentation\Dimensions\VisualInterDimensionalVariationGraph;

/**
 * The Neos Dimension module controller
 */
class DimensionController extends AbstractModuleController
{
    #[Flow\Inject]
    protected ContentDimensionSourceInterface $contentDimensionSource;

    #[Flow\Inject]
    protected InterDimensionalVariationGraph $interDimensionalVariationGraph;

    #[Flow\Inject]
    protected ContentDimensionZookeeper $contentDimensionZookeeper;

    public function indexAction(string $type = 'intraDimension', string $subgraphIdentifier = null): void
    {
        $graph = match ($type) {
            'intraDimension' => VisualIntraDimensionalVariationGraph::fromContentDimensionSource(
                $this->contentDimensionSource
            ),
            'interDimension' => $subgraphIdentifier
                ? VisualInterDimensionalVariationGraph::forInterDimensionalVariationSubgraph(
                    $this->interDimensionalVariationGraph,
                    $this->contentDimensionZookeeper->getAllowedDimensionSubspace()[$subgraphIdentifier]
                )
                : VisualInterDimensionalVariationGraph::forInterDimensionalVariationGraph(
                    $this->interDimensionalVariationGraph,
                    $this->contentDimensionSource
                ),
            default => null,
        };

        $this->view->assignMultiple([
            'availableGraphTypes' => ['intraDimension', 'interDimension'],
            'type' => $type,
            'selectedSubgraphIdentifier' => $subgraphIdentifier,
            'graph' => $graph
        ]);
    }
}
