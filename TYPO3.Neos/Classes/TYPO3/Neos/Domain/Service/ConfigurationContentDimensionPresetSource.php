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

/**
 * A Dimension Preset Source that gets presets from settings
 *
 * Everything is configured in Settings.yaml in path "TYPO3.TYPO3CR.contentDimensions".
 * @Flow\Scope("singleton")
 */
class ConfigurationContentDimensionPresetSource extends \TYPO3\TYPO3CR\Domain\Service\ConfigurationContentDimensionPresetSource implements ContentDimensionPresetSourceInterface {

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

}
