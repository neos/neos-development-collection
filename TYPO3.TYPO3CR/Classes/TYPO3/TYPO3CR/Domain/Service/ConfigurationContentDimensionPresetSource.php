<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\TYPO3CR\Exception;

/**
 * A Dimension Preset Source that gets presets from settings
 *
 * Everything is configured in Settings.yaml in path "TYPO3.TYPO3CR.contentDimensions".
 */
class ConfigurationContentDimensionPresetSource implements ContentDimensionPresetSourceInterface
{
    /**
     * Dimension presets configuration indexed by dimension name, see ContentDimensionPresetSourceInterface
     *
     * @var array
     */
    protected $configuration;

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
     * {@inheritdoc}
     */
    public function getAllDimensionCombinations()
    {
        $dimensionConfigurations = $this->getAllPresets();

        $dimensionValueCountByDimension = array();
        $possibleCombinationCount = 1;
        $combinations = array();

        foreach ($dimensionConfigurations as $dimensionName => $singleDimensionConfiguration) {
            $dimensionValueCountByDimension[$dimensionName] = count($singleDimensionConfiguration['presets']);
            $possibleCombinationCount = $possibleCombinationCount * $dimensionValueCountByDimension[$dimensionName];
        }

        foreach ($dimensionConfigurations as $dimensionName => $singleDimensionConfiguration) {
            for ($i = 0; $i < $possibleCombinationCount; $i++) {
                if (!isset($combinations[$i]) || !is_array($combinations[$i])) {
                    $combinations[$i] = array();
                }

                $currentDimensionCurrentPreset = current($dimensionConfigurations[$dimensionName]['presets']);
                $combinations[$i][$dimensionName] = $currentDimensionCurrentPreset['values'];

                if (!next($dimensionConfigurations[$dimensionName]['presets'])) {
                    reset($dimensionConfigurations[$dimensionName]['presets']);
                }
            }
        }

        return $combinations;
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
}
