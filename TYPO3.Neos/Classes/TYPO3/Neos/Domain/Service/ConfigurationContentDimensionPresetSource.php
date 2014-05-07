<?php
namespace TYPO3\Neos\Domain\Service;

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
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * A Dimension Preset Source that gets presets from settings
 *
 * Everything is configured in Settings.yaml in path "TYPO3.Neos.contentDimensions.dimensions".
 */
class ConfigurationContentDimensionPresetSource implements ContentDimensionPresetSourceInterface {

	/**
	 * Dimension presets configuration indexed by dimension name, see ContentDimensionPresetSourceInterface
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * {@inheritdoc}
	 */
	public function getAllPresets() {
		$sorter = new PositionalArraySorter($this->configuration);
		$sortedConfiguration = $sorter->toArray();

		foreach ($sortedConfiguration as &$dimensionConfiguration) {
			$sorter = new PositionalArraySorter($dimensionConfiguration['presets']);
			$dimensionConfiguration['presets'] = $sorter->toArray();
		}

		return $sortedConfiguration;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultPreset($dimensionName) {
		if (isset($this->configuration[$dimensionName]['defaultPreset']) && isset($this->configuration[$dimensionName]['presets'][$this->configuration[$dimensionName]['defaultPreset']])) {
			$preset = $this->configuration[$dimensionName]['presets'][$this->configuration[$dimensionName]['defaultPreset']];
			$preset['identifier'] = $this->configuration[$dimensionName]['defaultPreset'];
			return $preset;
		}
		return NULL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function findPresetByUriSegment($dimensionName, $uriSegment) {
		if (isset($this->configuration[$dimensionName])) {
			foreach ($this->configuration[$dimensionName]['presets'] as $presetIdentifier => $presetConfiguration) {
				if (isset($presetConfiguration['uriSegment']) && $presetConfiguration['uriSegment'] === $uriSegment) {
					$presetConfiguration['identifier'] = $presetIdentifier;
					return $presetConfiguration;
				}
			}
		}
		return NULL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function findPresetByDimensionValues($dimensionName, array $dimensionValues) {
		if (isset($this->configuration[$dimensionName])) {
			foreach ($this->configuration[$dimensionName]['presets'] as $presetIdentifier => $presetConfiguration) {
				if (isset($presetConfiguration['values']) && $presetConfiguration['values'] === $dimensionValues) {
					$presetConfiguration['identifier'] = $presetIdentifier;
					return $presetConfiguration;
				}
			}
		}
		return NULL;
	}

	/**
	 * @param array $configuration
	 * @return void
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

}
