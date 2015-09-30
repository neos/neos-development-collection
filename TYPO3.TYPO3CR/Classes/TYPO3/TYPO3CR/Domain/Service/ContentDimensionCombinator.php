<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Generates dimension combinations.
 *
 * @Flow\Scope("singleton")
 */
class ContentDimensionCombinator
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * Array of all possible dimension configurations allowed by configured presets.
     *
     * @return array
     */
    public function getAllAllowedCombinations()
    {
        $configuration = $this->contentDimensionPresetSource->getAllPresets();
        $dimensionCombinations = [];
        $dimensionNames = array_keys($configuration);
        $dimensionCount = count($dimensionNames);

        if ($dimensionCount === 0) {
            // This is correct, we have one allowed combination which is no dimension values (empty array).
            return [[]];
        }

        // Reset all presets first just to be sure
        foreach ($configuration as $dimensionName => &$dimensionConfiguration) {
            reset($dimensionConfiguration['presets']);
        }
        unset($dimensionConfiguration);

        while (true) {
            $skipCurrentCombination = false;
            $currentPresetCombination = [
                'withPresetIdentifiers' => [],
                'withDimensionValues' => []
            ];
            foreach ($dimensionNames as $dimensionName) {
                $presetIdentifierForDimension = key($configuration[$dimensionName]['presets']);
                $presetForDimension = current($configuration[$dimensionName]['presets']);

                if (!is_array($presetForDimension) || !isset($presetForDimension['values'])) {
                    $skipCurrentCombination = true;
                }

                $currentPresetCombination['withPresetIdentifiers'][$dimensionName] = $presetIdentifierForDimension;
                $currentPresetCombination['withDimensionValues'][$dimensionName] = $presetForDimension['values'];
            }

            if ($skipCurrentCombination === false && $this->contentDimensionPresetSource->isPresetCombinationAllowedByConstraints($currentPresetCombination['withPresetIdentifiers'])) {
                $dimensionCombinations[] = $currentPresetCombination['withDimensionValues'];
            }

            $nextDimension = 0;
            $hasValue = next($configuration[$dimensionNames[$nextDimension]]['presets']);
            while ($hasValue === false) {
                reset($configuration[$dimensionNames[$nextDimension]]['presets']);
                $nextDimension++;
                if (!isset($dimensionNames[$nextDimension])) {
                    // we have gone through all dimension combinations now.
                    return $dimensionCombinations;
                }
                $hasValue = next($configuration[$dimensionNames[$nextDimension]]['presets']);
            }
        }
    }
}
