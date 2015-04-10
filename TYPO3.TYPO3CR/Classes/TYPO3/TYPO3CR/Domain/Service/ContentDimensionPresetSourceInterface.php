<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An interface for a Content Dimension Preset source
 *
 * It allows to find a matching preset for a list of dimension values. Or calculate all dimension combinations for you (the matrix of all configured presets).
 *
 * Content Dimension Preset
 * ========================
 *
 * A Content Dimension Preset assigns an identifier to a (fallback) list of dimension values. It can have additional properties like UI label and
 * icon and further options for routing if needed.
 *
 * The default implementation ConfigurationContentDimensionPresetSource will read the available presets from settings.
 */
interface ContentDimensionPresetSourceInterface {

	/**
	 * Get the full presets configuration as an array
	 *
	 * Example:
	 *
	 *  'language':
	 *    defaultPreset: 'all'
	 *    label: 'Language'
	 *    icon: 'icon-language'
	 *    presets:
	 *      'all':
	 *        label: 'All languages'
	 *        values: ['mul_ZZ']
	 *        uriSegment: 'intl'
	 *      'de_DE':
	 *        label: 'Deutsch (Deutschland)'
	 *        values: ['de_DE', 'de_ZZ', 'mul_ZZ']
	 *        uriSegment: 'deutsch'
	 *
	 * @return array Presets configuration, indexed by dimension identifier
	 */
	public function getAllPresets();

	/**
	 * Get the default preset of a dimension
	 *
	 * @param string $dimensionName The dimension name where the default preset should be returned
	 * @return array The preset configuration, including the identifier as key "identifier"
	 */
	public function getDefaultPreset($dimensionName);

	/**
	 * Find a dimension preset by dimension values
	 *
	 * @param string $dimensionName
	 * @param array $dimensionValues
	 * @return array The preset configuration, including the identifier as key "identifier" or NULL if none was found
	 */
	public function findPresetByDimensionValues($dimensionName, array $dimensionValues);

	/**
	 * Returns a list of presets of the specified dimension which are allowed in combination with the given presets
	 * of other dimensions.
	 *
	 * @param string $dimensionName Name of the dimension to return presets for
	 * @param array $preselectedDimensionPresets An array of dimension name and preset identifier specifying the presets which are already selected
	 * @return array An array of presets only for the dimension specified in $dimensionName. Structure is: array($dimensionName => array('presets' => array(...))
	 */
	public function getAllowedDimensionPresetsAccordingToPreselection($dimensionName, array $preselectedDimensionPresets);

	/**
	 * Checks if the given combination of presets is allowed, according to possibly defined constraints in the
	 * content dimension configuration.
	 *
	 * @param array $dimensionsNamesAndPresetIdentifiers Preset pairs, for example array('language' => 'de', 'country' => 'GER', 'persona' => 'clueless')
	 * @return boolean
	 */
	public function isPresetCombinationAllowedByConstraints(array $dimensionsNamesAndPresetIdentifiers);

}
