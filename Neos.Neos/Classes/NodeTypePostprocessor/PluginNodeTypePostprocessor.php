<?php
namespace Neos\Neos\NodeTypePostprocessor;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Neos\Service\PluginService;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\NodeTypePostprocessor\NodeTypePostprocessorInterface;

/**
 * This Processor updates the PluginViews NodeType with the existing
 * Plugins and it's corresponding available Views
 */
class PluginNodeTypePostprocessor implements NodeTypePostprocessorInterface
{
    /**
     * @var ConfigurationManager
     * @Flow\Inject
     */
    protected $configurationManager;

    /**
     * @var PluginService
     * @Flow\Inject
     */
    protected $pluginService;

    /**
     * Returns the processed Configuration
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeType $nodeType (uninitialized) The node type to process
     * @param array $configuration input configuration
     * @param array $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options)
    {
        $pluginViewDefinitions = $this->pluginService->getPluginViewDefinitionsByPluginNodeType($nodeType);
        if ($pluginViewDefinitions === []) {
            return;
        }
        $configuration['ui']['inspector']['groups']['pluginViews'] = [
            'position' => '9999',
            'label' => 'Plugin Views'
        ];
        $configuration['properties']['views'] = [
            'type' => 'string',
            'ui' => [
                'inspector' => [
                    'group' => 'pluginViews',
                    'position' => '20',
                    'editor' => 'Neos.Neos/Inspector/Editors/PluginViewsEditor'
                ]
            ]
        ];
    }
}
