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
interface ContentDimensionPresetSourceInterface
{
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
     * Returns an array with all possible combinations of configured dimension presets. This should result in a full description of all node contexts (in terms of dimensions)
     * that could appear in this system.
     *
     * The resulting array will be numerically indexed with every entry being an array of "dimension name" => "configured fallbacks" for each dimension
     *
     * @return array
     */
    public function getAllDimensionCombinations();
}
