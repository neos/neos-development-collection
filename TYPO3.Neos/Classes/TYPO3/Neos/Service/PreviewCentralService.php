<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Service to get available editing mode per node type
 *
 * @Flow\Scope("singleton")
 */
class PreviewCentralService
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\InjectConfiguration(path="userInterface.editPreviewModes")
     * @var array
     */
    protected $settings;

    /**
     * Find all edit / preview modes for the given node type
     *
     * @param string|NodeType $nodeType
     * @return array
     */
    public function findEditPreviewModesByNodeType($nodeType)
    {
        if (!$nodeType instanceof NodeType) {
            $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
        }
        $settings = $this->settings;

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

        return $configuration->toArray();
    }
}
