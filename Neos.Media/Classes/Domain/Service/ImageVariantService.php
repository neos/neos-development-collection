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
     * Return all presets defined in 'Settings.Neos.Media.yaml' with presetName as key
     *
     * @param string $presetIdentifier
     * @return VariantPreset[]
     */
    public function getAllPresetsOfIdentifier(string $presetIdentifier): array
    {
        $variantPresetConfigurations = $this->getAllPresetsByConfiguration();

        $variantPresetName = array_key_exists($presetIdentifier, $variantPresetConfigurations);

        if (!$variantPresetName) {
            // the given presetIdentifier ist not included in variantPresetConfigurations
            return [];
        }

        return [$variantPresetConfigurations[$presetIdentifier]];
    }

    /**
     * Return presets from 'Settings.Neos.Media.yaml'
     *
     * @return VariantPreset[]
     */
    public function getAllPresetsByConfiguration(): array
    {
        return $this->assetVariantGenerator->getVariantPresets();
    }
}
