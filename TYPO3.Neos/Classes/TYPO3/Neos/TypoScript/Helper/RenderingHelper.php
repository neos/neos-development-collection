<?php
namespace TYPO3\Neos\TypoScript\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Render Content Dimension Names, Node Labels
 *
 * These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*
 */
class RenderingHelper implements ProtectedContextAwareInterface {

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
	public function injectConfigurationManager(ConfigurationManager $configurationManager) {
		$this->contentDimensionsConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.TYPO3CR.contentDimensions');
	}

	/**
	 * Render a human-readable description for the passed $dimensions
	 *
	 * @param array $dimensions
	 * @return string
	 */
	public function renderDimensions(array $dimensions) {
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
	protected function findPresetInDimension(array $dimensionConfiguration, $dimensionValue) {
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

		return NULL;
	}

	/**
	 * Render the label for the given $nodeTypeName
	 *
	 * @param string $nodeTypeName
	 * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
	 * @return string
	 */
	public function labelForNodeType($nodeTypeName) {
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
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}
}