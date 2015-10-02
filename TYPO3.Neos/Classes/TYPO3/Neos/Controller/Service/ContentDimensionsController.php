<?php
namespace TYPO3\Neos\Controller\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
 * REST service controller for managing content dimensions
 */
class ContentDimensionsController extends ActionController
{
    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => 'TYPO3\Fluid\View\TemplateView',
        'json' => 'TYPO3\Flow\Mvc\View\JsonView'
    );

    /**
     * @var array
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * @var ContentDimensionPresetSourceInterface
     * @Flow\Inject
     */
    protected $contentDimensionPresetSource;

    /**
     * Returns the full content dimension presets as JSON object; see
     * ContentDimensionPresetSourceInterface::getAllPresets() for a format
     * description.
     *
     * @return void
     */
    public function indexAction()
    {
        if ($this->view instanceof JsonView) {
            $this->view->assign('value', $this->contentDimensionPresetSource->getAllPresets());
        } else {
            $this->view->assign('contentDimensionsPresets', $this->contentDimensionPresetSource->getAllPresets());
        }
    }
}
