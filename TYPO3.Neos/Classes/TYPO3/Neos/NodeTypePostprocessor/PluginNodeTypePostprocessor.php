<?php
namespace TYPO3\Neos\NodeTypePostprocessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Neos\Service\PluginService;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\NodeTypePostprocessor\NodeTypePostprocessorInterface;

/**
 * This Processor updates the PluginViews NodeType with the existing
 * Plugins and it's corresponding available Views
 */
class PluginNodeTypePostprocessor implements NodeTypePostprocessorInterface {

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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType (uninitialized) The node type to process
	 * @param array $configuration input configuration
	 * @param array $options The processor options
	 * @return void
	 */
	public function process(NodeType $nodeType, array &$configuration, array $options) {
		$pluginViewDefinitions = $this->pluginService->getPluginViewDefinitionsByPluginNodeType($nodeType);
		if ($pluginViewDefinitions === array()) {
			return;
		}
		$configuration['ui']['inspector']['groups']['pluginViews'] = array(
			'position' => '9999',
			'label' => 'Plugin Views'
		);
		$configuration['properties']['views'] = array(
			'type' => 'string',
			'ui' => array(
				'inspector' => array(
					'group' => 'pluginViews',
					'position' => '20',
					'editor' => 'TYPO3.Neos/Inspector/Editors/PluginViewsEditor'
				)
			)
		);
	}
}
