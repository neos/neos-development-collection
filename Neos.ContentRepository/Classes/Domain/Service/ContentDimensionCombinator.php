<?php
namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
