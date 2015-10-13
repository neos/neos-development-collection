<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A Dimension Preset Source that gets presets from settings
 *
 * Everything is configured in Settings.yaml in path "TYPO3.TYPO3CR.contentDimensions".
 * @Flow\Scope("singleton")
 */
class ConfigurationContentDimensionPresetSource extends \TYPO3\TYPO3CR\Domain\Service\ConfigurationContentDimensionPresetSource implements ContentDimensionPresetSourceInterface
{
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
