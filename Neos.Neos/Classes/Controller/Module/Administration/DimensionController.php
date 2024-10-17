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

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Presentation\Dimensions\VisualInterDimensionalVariationGraph;
use Neos\Neos\Presentation\Dimensions\VisualIntraDimensionalVariationGraph;

/**
 * The Neos Dimension module controller
 */
class DimensionController extends AbstractModuleController
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function indexAction(string $type = 'intraDimension', string $dimensionSpacePointHash = null): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $dimensionSpacePoint = $dimensionSpacePointHash
            ? $contentRepository->getVariationGraph()->getDimensionSpacePoints()[$dimensionSpacePointHash]
            : null;
        $graph = match ($type) {
            'intraDimension' => VisualIntraDimensionalVariationGraph::fromContentDimensionSource(
                $contentRepository->getContentDimensionSource()
            ),
            'interDimension' => $dimensionSpacePoint
                ? VisualInterDimensionalVariationGraph::forInterDimensionalVariationSubgraph(
                    $contentRepository->getVariationGraph(),
                    $dimensionSpacePoint
                )
                : VisualInterDimensionalVariationGraph::forInterDimensionalVariationGraph(
                    $contentRepository->getVariationGraph(),
                    $contentRepository->getContentDimensionSource()
                ),
            default => null,
        };

        $this->view->assignMultiple([
            'availableGraphTypes' => ['intraDimension', 'interDimension'],
            'type' => $type,
            'selectedDimensionSpacePointHash' => $dimensionSpacePointHash,
            'graph' => $graph
        ]);
    }
}
