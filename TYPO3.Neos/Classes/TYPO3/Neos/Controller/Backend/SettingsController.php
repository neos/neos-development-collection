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
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\PositionalArraySorter;
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
     * @param string $nodeType
     * @return string
     */
    public function editPreviewAction($nodeType)
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
        $settings = Arrays::getValueByPath($this->settings, 'userInterface.editPreviewModes');

        $allowedPreviewModes = $editPreviewModes = [];
        $configurationPath = 'ui.editPreviewModes';
        if ($nodeType->hasConfiguration($configurationPath) && is_array($nodeType->getConfiguration($configurationPath))) {
            $editPreviewModes = $nodeType->getConfiguration($configurationPath);
            $allowedPreviewModes = array_keys(array_filter($editPreviewModes, function ($mode) {
                return isset($mode['enabled']) && $mode['enabled'] === true;
            }));
        }
        if ($editPreviewModes !== []) {
            foreach ($settings as $name => $configuration) {
                if (!in_array($name, $allowedPreviewModes)) {
                    unset($settings[$name]);
                }
                if (isset($editPreviewModes[$name]['position'])) {
                    $settings[$name]['position'] = (integer)$editPreviewModes[$name]['position'];
                }
            }
        }

        $configuration = new PositionalArraySorter($settings);

        $this->response->setHeader('Content-Type', 'application/json');
        return json_encode($configuration->toArray());
    }
}
