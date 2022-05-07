<?php
namespace Neos\Neos\Controller\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\BackendUserTranslationTrait;

/**
 * REST service controller for managing content dimensions
 */
class ContentDimensionsController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @var array<string,string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => JsonView::class
    ];

    /**
     * @var array<int,string>
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * @var ContentDimensionSourceInterface
     * @Flow\Inject
     */
    protected $contentDimensionPresetSource;

    #[Flow\Inject]
    protected ContentDimensionZookeeper $contentDimensionZookeeper;

    /**
     * Returns the full content dimensions presets as JSON object; see
     * ContentDimensionPresetSourceInterface::getAllPresets() for a format description.
     *
     * @return void
     */
    public function indexAction()
    {
        if ($this->view instanceof JsonView) {
            $this->view->assign('value', $this->contentDimensionZookeeper->getAllowedDimensionSubspace());
        } else {
            $this->view->assign('contentDimensionsPresets', $this->contentDimensionZookeeper->getAllowedDimensionSubspace());
        }
    }

    /**
     * Returns only presets of the dimension specified by $dimensionName.
     * But even though only one dimension is returned, the output follows the structure
     * as described in ContentDimensionPresetSourceInterface::getAllPresets().
     *
     * It is possible to pass a selection of presets as a filter. In that case, $chosenDimensionPresets must be an array
     * of one or more dimension names (key) and preset names (value). The returned list will then only contain dimension
     * presets which are allowed in combination with the given presets.
     *
     * Example: Given that $chosenDimensionPresets = array('country' => 'US')
     * and that a second dimension "language" exists and $dimensionName is "language.
     * This method will now display a list of dimension presets for the dimension
     * "language" which are allowed in combination with the country "US".
     *
     * @param string $dimensionName Name of the dimension to return presets for
     * @param array<mixed> $chosenDimensionPresets An optional array of dimension names and a single preset per dimension
     * @return void
     */
    public function showAction($dimensionName, $chosenDimensionPresets = [])
    {
        if ($chosenDimensionPresets === []) {
            $contentDimensionsAndPresets = $this->contentDimensionPresetSource->getContentDimensionsOrderedByPriority();
            if (!isset($contentDimensionsAndPresets[$dimensionName])) {
                $this->throwStatus(404, sprintf('The dimension %s does not exist.', $dimensionName));
            }
            $contentDimensionsAndPresets = [$dimensionName => $contentDimensionsAndPresets[$dimensionName]];
        } else {
            $contentDimensionsAndPresets = $this->contentDimensionPresetSource
                ->getDimension(new ContentDimensionIdentifier($dimensionName));
        }

        if ($this->view instanceof JsonView) {
            $this->view->assign('value', $contentDimensionsAndPresets);
        } else {
            $this->view->assign('dimensionName', $dimensionName);
            $this->view->assign('contentDimensionsPresets', $contentDimensionsAndPresets);
        }
    }
}
