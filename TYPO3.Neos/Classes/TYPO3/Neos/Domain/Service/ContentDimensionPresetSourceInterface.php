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
 * An interface for a Content Dimension Preset source
 *
 * It allows to resolve a Content Dimension Preset for a given dimension and urlSegment or find a matching
 * preset for a list of dimension values.
 *
 * Content Dimension Preset
 * ========================
 *
 * A Content Dimension Preset assigns an identifier to a list of dimension values. It has UI properties for a label and
 * icon and further options for routing.
 *
 * The default implementation ConfigurationContentDimensionPresetSource will read the available presets from settings.
 */
interface ContentDimensionPresetSourceInterface {

	/**
	 * Get the full presets configuration as an array
	 *
	 * Example:
	 *
	 *  'languages':
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
	 * Find a dimension preset by URI identifier
	 *
	 * @param string $dimensionName The dimension name where the preset should be searched
	 * @param string $uriSegment The URI segment for a Content Dimension Preset
	 * @return array The preset configuration, including the identifier as key "identifier" or NULL if none was found
	 */
	public function findPresetByUriSegment($dimensionName, $uriSegment);

	/**
	 * Find a dimension preset by dimension values
	 *
	 * @param string $dimensionName
	 * @param array $dimensionValues
	 * @return array The preset configuration, including the identifier as key "identifier" or NULL if none was found
	 */
	public function findPresetByDimensionValues($dimensionName, array $dimensionValues);

}
