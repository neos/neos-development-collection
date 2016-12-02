<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;

/**
 * Render Content Dimension Names, Node Labels
 *
 * These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*
 */
class RenderingHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var array
     */
    protected $contentDimensionsConfiguration;

    /**
     * @param ConfigurationManager $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->contentDimensionsConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepository.contentDimensions');
    }

    /**
     * Render a human-readable description for the passed $dimensions
     *
     * @param array $dimensions
     * @return string
     */
    public function renderDimensions(array $dimensions)
    {
        $rendered = array();
        foreach ($dimensions as $dimensionIdentifier => $dimensionValue) {
            $dimensionConfiguration = $this->contentDimensionsConfiguration[$dimensionIdentifier];
            $preset = $this->findPresetInDimension($dimensionConfiguration, $dimensionValue);
            $rendered[] = $dimensionConfiguration['label'] . ' ' . $preset['label'];
        }

        return implode(', ', $rendered);
    }

    /**
     * @param array $dimensionConfiguration
     * @param string $dimensionValue
     * @return array the preset matching $dimensionValue
     */
    protected function findPresetInDimension(array $dimensionConfiguration, $dimensionValue)
    {
        foreach ($dimensionConfiguration['presets'] as $preset) {
            if (!isset($preset['values'])) {
                continue;
            }
            foreach ($preset['values'] as $value) {
                if ($value === $dimensionValue) {
                    return $preset;
                }
            }
        }

        return null;
    }

    /**
     * Render the label for the given $nodeTypeName
     *
     * @param string $nodeTypeName
     * @throws NodeTypeNotFoundException
     * @return string
     */
    public function labelForNodeType($nodeTypeName)
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            $explodedNodeTypeName = explode(':', $nodeTypeName);

            return end($explodedNodeTypeName);
        }

        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        return $nodeType->getLabel();
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
