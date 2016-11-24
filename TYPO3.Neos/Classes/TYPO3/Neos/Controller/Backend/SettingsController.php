<?php
namespace TYPO3\Neos\Controller\Backend;

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
use TYPO3\Neos\Service\PreviewCentralService;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * @Flow\Scope("singleton")
 */
class SettingsController extends ActionController
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @var PreviewCentralService
     * @Flow\Inject
     */
    protected $previewCentralService;

    /**
     * @param string $nodeType
     * @return string
     */
    public function editPreviewAction($nodeType)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        return json_encode($this->previewCentralService->findEditPreviewModesByNodeType($nodeType));
    }
}
