<?php
namespace TYPO3\Neos\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;
use TYPO3\TypoScript\Exception as TypoScriptException;

/**
 * A TypoScript Dimension Menu object
 *
 * Main Options:
 * - dimension (required, string): name of the dimension which this menu should be based on. Example: "language".
 * - presets (optional, array): If set, the presets are not loaded from the Settings, but instead taken from this property
 */
class DimensionMenuImplementation extends AbstractMenuImplementation {

	/**
	 * @Flow\Inject
	 * @var ConfigurationContentDimensionPresetSource
	 */
	protected $configurationContentDimensionPresetSource;

	/**
	 * @return string
	 */
	public function getDimension() {
		return $this->tsValue('dimension');
	}

	/**
	 * @return array
	 */
	public function getPresets() {
		return $this->tsValue('presets');
	}

	/**
	 * @return array
	 */
	public function buildItems() {
		$output = array();

		foreach ($this->getPresetsInCorrectOrder() as $presetName => $presetConfiguration) {
			$q = new FlowQuery(array($this->currentNode));
			$nodeInOtherDimension = $q->context(
				array(
					'dimensions' => array(
						'language' => $presetConfiguration['values']
					),
					'targetDimensions' => array(
						'language' => reset($presetConfiguration['values'])
					)
				)
			)->get(0);

			if ($nodeInOtherDimension !== NULL && $this->isNodeHidden($nodeInOtherDimension)) {
				$nodeInOtherDimension = NULL;
			}

			$item = array(
				'node' => $nodeInOtherDimension,
				'state' => $this->calculateItemState($nodeInOtherDimension),
				'label' => $presetConfiguration['label'],
				'presetName' => $presetName,
				'preset' => $presetConfiguration
			);
			$output[] = $item;
		}

		return $output;
	}

	/**
	 * Return the presets in the correct order, taking possibly-overridden presets into account
	 *
	 * @return array
	 * @throws TypoScriptException
	 */
	protected function getPresetsInCorrectOrder() {
		$dimension = $this->getDimension();

		$allDimensions = $this->configurationContentDimensionPresetSource->getAllPresets();
		if (!isset($allDimensions[$dimension])) {
			throw new TypoScriptException(sprintf('Dimension "%s" was referenced, but not configured.', $dimension), 1415880445);
		}
		$allPresetsOfChosenDimension = $allDimensions[$dimension]['presets'];

		$presetNames = $this->getPresets();
		if ($presetNames === NULL) {
			$presetNames = array_keys($allPresetsOfChosenDimension);
		} elseif (!is_array($presetNames)) {
			throw new TypoScriptException('The configured preset in TypoScript was no array.', 1415888652);
		}

		$resultingPresets = array();
		foreach ($presetNames as $presetName) {
			if (!isset($allPresetsOfChosenDimension[$presetName])) {
				throw new TypoScriptException(sprintf('The preset name "%s" does not exist in the chosen dimension. Valid values are: %s', $presetName, implode(', ', array_keys($allPresetsOfChosenDimension))), 1415889492);
			}
			$resultingPresets[$presetName] = $allPresetsOfChosenDimension[$presetName];
		}

		return $resultingPresets;
	}
}