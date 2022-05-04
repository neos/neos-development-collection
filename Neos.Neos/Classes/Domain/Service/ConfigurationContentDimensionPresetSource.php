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

use Neos\ContentRepository\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\PositionalArraySorter;

/**
 * A Dimension Preset Source that gets presets from settings
 *
 * Everything is configured in Settings.yaml in path "Neos.ContentRepository.contentDimensions".
 * @Flow\Scope("singleton")
 */
class ConfigurationContentDimensionPresetSource implements ContentDimensionPresetSourceInterface
{
    /**
     * Dimension presets configuration indexed by dimension name, see ContentDimensionPresetSourceInterface
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * {@inheritdoc}
     */
    public function getAllPresets()
    {
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
    public function getDefaultPreset($dimensionName)
    {
        if (isset($this->configuration[$dimensionName]['defaultPreset']) && isset($this->configuration[$dimensionName]['presets'][$this->configuration[$dimensionName]['defaultPreset']])) {
            $preset = $this->configuration[$dimensionName]['presets'][$this->configuration[$dimensionName]['defaultPreset']];
            $preset['identifier'] = $this->configuration[$dimensionName]['defaultPreset'];

            return $preset;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findPresetByDimensionValues($dimensionName, array $dimensionValues)
    {
        if (isset($this->configuration[$dimensionName])) {
            foreach ($this->configuration[$dimensionName]['presets'] as $presetIdentifier => $presetConfiguration) {
                if (isset($presetConfiguration['values']) && $presetConfiguration['values'] === $dimensionValues) {
                    $presetConfiguration['identifier'] = $presetIdentifier;

                    return $presetConfiguration;
                }
            }
        }

        return null;
    }

    /**
     * Returns a list of presets of the specified dimension which are allowed in combination with the given presets
     * of other dimensions.
     *
     * @param string $dimensionName Name of the dimension to return presets for
     * @param array $preselectedDimensionPresets An array of dimension name and preset identifier specifying the presets which are already selected
     * @return array An array of presets only for the dimension specified in $dimensionName. Structure is: array($dimensionName => array('presets' => array(...))
     */
    public function getAllowedDimensionPresetsAccordingToPreselection($dimensionName, array $preselectedDimensionPresets)
    {
        if (!isset($this->configuration[$dimensionName])) {
            return null;
        }

        $dimensionConfiguration = [$dimensionName => $this->configuration[$dimensionName]];
        $sorter = new PositionalArraySorter($dimensionConfiguration[$dimensionName]['presets']);
        $dimensionConfiguration[$dimensionName]['presets'] = $sorter->toArray();

        foreach (array_keys($dimensionConfiguration[$dimensionName]['presets']) as $presetIdentifier) {
            $currentPresetCombination = $preselectedDimensionPresets;
            $currentPresetCombination[$dimensionName] = $presetIdentifier;
            if (!$this->isPresetCombinationAllowedByConstraints($currentPresetCombination)) {
                unset($dimensionConfiguration[$dimensionName]['presets'][$presetIdentifier]);
            }
        }

        return $dimensionConfiguration;
    }

    /**
     * Checks if the given combination of presets is allowed, according to possibly defined constraints in the
     * content dimension configuration.
     *
     * @param array $dimensionsNamesAndPresetIdentifiers Preset pairs, for example array('language' => 'de', 'country' => 'GER', 'persona' => 'clueless')
     * @return boolean
     */
    public function isPresetCombinationAllowedByConstraints(array $dimensionsNamesAndPresetIdentifiers)
    {
        foreach ($dimensionsNamesAndPresetIdentifiers as $dimensionName => $presetIdentifier) {
            if (!isset($this->configuration[$dimensionName]) || !isset($this->configuration[$dimensionName]['presets'][$presetIdentifier])) {
                return false;
            }
            foreach ($this->configuration as $currentDimensionName => $dimensionConfiguration) {
                if (!isset($dimensionsNamesAndPresetIdentifiers[$currentDimensionName])) {
                    continue;
                }
                $currentPresetIdentifier = $dimensionsNamesAndPresetIdentifiers[$currentDimensionName];
                if (isset($dimensionConfiguration['presets'][$currentPresetIdentifier]['constraints'])) {
                    $constraintsResult = $this->isPresetAllowedByConstraints($dimensionName, $presetIdentifier, $dimensionConfiguration['presets'][$currentPresetIdentifier]['constraints']);
                    if ($constraintsResult === false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Checks if the given preset of the specified dimension is allowed according to the given constraints
     *
     * @param string $dimensionName Name of the dimension the preset belongs to
     * @param string $presetIdentifier Identifier of the preset to check
     * @param array $constraints Constraints to use for the check
     * @return boolean
     */
    protected function isPresetAllowedByConstraints($dimensionName, $presetIdentifier, array $constraints)
    {
        if (!array_key_exists($dimensionName, $constraints)) {
            return true;
        }
        if (array_key_exists($presetIdentifier, $constraints[$dimensionName]) && $constraints[$dimensionName][$presetIdentifier] === true) {
            return true;
        }
        if (array_key_exists($presetIdentifier, $constraints[$dimensionName]) && $constraints[$dimensionName][$presetIdentifier] === false) {
            return false;
        }
        if (array_key_exists('*', $constraints[$dimensionName])) {
            return (boolean)$constraints[$dimensionName]['*'];
        }

        return true;
    }

    /**
     * @param array $configuration
     * @return void
     * @throws Exception
     */
    public function setConfiguration(array $configuration)
    {
        foreach ($configuration as $dimensionName => $dimensionConfiguration) {
            $defaultPreset = $dimensionConfiguration['defaultPreset'];
            if (!isset($dimensionConfiguration['presets'][$defaultPreset])) {
                throw new Exception(sprintf('The preset "%s" which was configured to be the default preset for the content dimension "%s" does not exist. Please check your content dimension settings.', $defaultPreset, $dimensionName), 1401093863);
            }
        }
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function findPresetsByTargetValues(array $targetValues)
    {
        $matchingPresets = [];
        $allPresets = $this->getAllPresets();

        foreach ($targetValues as $dimensionName => $dimensionValues) {
            $matchingPresets[$dimensionName] = array_reduce($allPresets[$dimensionName]['presets'], function ($primaryPreset, $presetForDimension) use ($dimensionValues) {
                return $this->comparePresetsForTargetValue($presetForDimension, $dimensionValues, $primaryPreset);
            }, null);

            if ($matchingPresets[$dimensionName] !== null) {
                continue;
            }

            $matchingPresets[$dimensionName] = [
                'label' => reset($dimensionValues),
                'values' =>  $dimensionValues
            ];
        }

        return $matchingPresets;
    }

    /**
     * Compares the given $possibleBetterPreset to the $targetValues (based on the position of the contained values)
     * and returns either $possibleBetterPreset or the $currentBestPreset, depending on the result.
     *
     * @param array $possibleBetterPreset
     * @param array $targetValues
     * @param array $currentBestPreset
     * @return array
     */
    protected function comparePresetsForTargetValue(array $possibleBetterPreset, array $targetValues, array $currentBestPreset = null)
    {
        if (!isset($possibleBetterPreset['values'][0])) {
            return $currentBestPreset;
        }

        if ($possibleBetterPreset['values'] === $targetValues) {
            return $possibleBetterPreset;
        }

        if ($possibleBetterPreset['values'][0] === reset($targetValues)) {
            return $possibleBetterPreset;
        }

        foreach ($targetValues as $targetValue) {
            if ($currentBestPreset === null && in_array($targetValue, $possibleBetterPreset['values'])) {
                return $possibleBetterPreset;
            }
        }

        return $currentBestPreset;
    }

    /**
     * {@inheritdoc}
     */
    public function findPresetByUriSegment($dimensionName, $uriSegment)
    {
        if (isset($this->configuration[$dimensionName])) {
            foreach ($this->configuration[$dimensionName]['presets'] as $presetIdentifier => $presetConfiguration) {
                if (isset($presetConfiguration['uriSegment']) && $presetConfiguration['uriSegment'] === $uriSegment) {
                    $presetConfiguration['identifier'] = $presetIdentifier;
                    return $presetConfiguration;
                }
            }
        }
        return null;
    }
}
