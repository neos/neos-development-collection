<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


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

    /**
     * Finds for each configured dimension the best matching preset based on given target value for that dimension.
     *
     * The $targetValues array should have the dimension as key and the target value (single value) as value.
     *
     * @param array $targetValues
     * @return array
     */
    public function findPresetsByTargetValues(array $targetValues);

    /**
     * Find a dimension preset by URI identifier
     *
     * @param string $dimensionName The dimension name where the preset should be searched
     * @param string $uriSegment The URI segment for a Content Dimension Preset
     * @return array The preset configuration, including the identifier as key "identifier" or NULL if none was found
     */
    public function findPresetByUriSegment($dimensionName, $uriSegment);
}
