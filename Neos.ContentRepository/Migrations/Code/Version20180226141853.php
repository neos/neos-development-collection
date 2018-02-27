<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Exception;
use Neos\Utility\Arrays;

/**
 * Update contentDimension Settings and convert presets to values and specialization
 */
class Version20180226141853 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.ContentRepository-20180226141853';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(
            'Settings',
            function (&$configuration) {
                if ($dimensionConfigurations = Arrays::getValueByPath($configuration, 'Neos.ContentRepository.contentDimensions')) {
                    foreach ($dimensionConfigurations as $dimensionName => $dimensionConfiguration) {
                        $updatedConfiguration = $this->updateDimensionConfiguration($dimensionName, $dimensionConfiguration);
                        if ($updatedConfiguration !== $dimensionConfiguration) {
                            $configuration['Neos']['ContentRepository']['contentDimensions'][$dimensionName] = $updatedConfiguration;
                        }
                    }
                }
            },
            true
        );
    }

    /**
     * @param string $dimensionConfiguration
     * @param array $dimensionConfiguration
     */
    protected function updateDimensionConfiguration($dimensionName, $dimensionConfiguration)
    {
        if (array_key_exists('presets', $dimensionConfiguration)) {
            $unconvertedDimensionPresets = [];

            // presets ordered by length of fallback chain
            $presets = array_filter($dimensionConfiguration['presets']);
            uasort (
                $presets,
                function($item1, $item2) {
                    return count($item1['values']) <=> count($item2['values']);
                }
            );

            // create values and specialisations
            foreach ($presets as $presetName => $presetConfiguration) {
                $values = array_reverse($presetConfiguration['values']);
                $valueConfiguration = $this->convertDimensionPresetConfigurationToDimensionValueConfiguration($presetConfiguration);
                $path = implode('.specializations.', $values);
                $parentPath = implode('.specializations.', array_slice($values,0, -1));
                if ($parentPath && Arrays::getValueByPath($dimensionConfiguration, 'values.' . $parentPath) === null) {
                    $unconvertedDimensionPresets[] = $presetName;
                } else {
                    $dimensionConfiguration = Arrays::setValueByPath($dimensionConfiguration, 'values.' . $path, $valueConfiguration);
                }
            }

            // delete presets or throw exception if unhandled dimension presets are detected#
            if ($unconvertedDimensionPresets) {
                throw new Exception(sprintf('The content dimension preset-configurations %s could not be converted to dimension value configurations.', implode (', ', $unconvertedDimensionPresets)));
            } else {
                unset($dimensionConfiguration['presets']);
            }
        }

        return $dimensionConfiguration;
    }

    /**
     * @param array $presetConfiguration
     * @return array
     */
    protected function convertDimensionPresetConfigurationToDimensionValueConfiguration(array $presetConfiguration)
    {
        $result = $presetConfiguration;
        if (array_key_exists('values', $presetConfiguration)) {
            unset ($result['values']);
        }
        if (array_key_exists('uriSegment', $result)) {
            $result['resolution']['value'] = $result['uriSegment'];
            unset ($result['uriSegment']);
        }
        return $result;
    }
}
