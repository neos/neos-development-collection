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

use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Presentation\Model\Svg;

/**
 * The Neos Dimension module controller
 */
class DimensionController extends AbstractModuleController
{
    /**
     * @Flow\Inject(lazy=false)
     * @var DimensionSpace\InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * @Flow\Inject(lazy=false)
     * @var Dimension\ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;


    /**
     * @param string $type
     * @param string $subgraphIdentifier
     * @return void
     * @todo fix me
     */
    public function indexAction(string $type = 'intraDimension', string $subgraphIdentifier = null)
    {
        switch ($type) {
            case 'intraDimension':
                $graph = new Svg\IntraDimensionalVariationGraph($this->contentDimensionSource);
                break;
            case 'interDimension':
                $graph = new Svg\InterDimensionalFallbackGraph(
                    $this->interDimensionalVariationGraph,
                    $this->contentDimensionSource,
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
