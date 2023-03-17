<?php

namespace Neos\Media\Domain\Service;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\ValueObject\Configuration\VariantPreset;
use Neos\Flow\Annotations as Flow;

/**
 * An imageVariant service
 *
 * @Flow\Scope("singleton")
 */
class ImageVariantService
{
    public function __construct(
        private AssetVariantGenerator $assetVariantGenerator
    ) {
    }

    /**
     * Return all presets defined for provided identifier in 'Settings.Neos.Media.yaml' configuration
     *
     * @param string $identifier
     * @return VariantPreset[]
     */
    public function getAllPresetsOfIdentifier(string $identifier): array
    {
        /** @var VariantPreset[][] $variantPresetConfigurations */
        $variantPresetConfigurations = $this->getAllPresetsByConfiguration();

        $variantPresetName = array_key_exists($identifier, $variantPresetConfigurations);

        if (!$variantPresetName) {
            // the given presetIdentifier ist not included in variantPresetConfigurations
            return [];
        }

        return $variantPresetConfigurations[$identifier];
    }

    /**
     * Return presets from 'Settings.Neos.Media.yaml' configuration
     *
     * @return VariantPreset[]
     */
    public function getAllPresetsByConfiguration(): array
    {
        $presets = [];
        $variantPresetConfigs = $this->assetVariantGenerator->getVariantPresets();

        foreach ($variantPresetConfigs as $presetsConfig) {
            $variantPresetName = array_search($presetsConfig, $variantPresetConfigs);
            $presetKeys = array_keys($presetsConfig->variants());
            foreach ($presetKeys as $preset) {
                $presets[(string)$variantPresetName][] = $preset;
            }
        }

        return $presets;
    }
}
