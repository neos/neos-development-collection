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

use Neos\ContentRepository\Service\FallbackGraphService;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\AbstractModuleController;

/**
 * The Neos Dimension module controller
 */
class DimensionController extends AbstractModuleController
{
    /**
     * @param string $type
     * @param string $subgraphIdentifier
     * @return void
     */
    public function indexAction(string $type = 'intraDimension', string $subgraphIdentifier = null)
    {
        switch ($type) {
            case 'intraDimension':
                $graph = new \Neos\Neos\Presentation\VisualIntraDimensionalVariationGraph($this->fallbackGraphService->getIntraDimensionalFallbackGraph());
                break;
            case 'interDimension':
                $graph = new \Neos\Neos\Presentation\VisualInterDimensionalVariationGraph(
                    $this->fallbackGraphService->getInterDimensionalFallbackGraph(),
                    $this->fallbackGraphService->getIntraDimensionalFallbackGraph(),
                    $subgraphIdentifier
                );
                break;
            default:
                $graph = null;
        }
        $this->view->assignMultiple([
            'availableGraphTypes' => ['intraDimension', 'interDimension'],
            'type' => $type,
            'selectedSubgraphIdentifier' => $subgraphIdentifier,
            'graph' => $graph
        ]);
    }
}
