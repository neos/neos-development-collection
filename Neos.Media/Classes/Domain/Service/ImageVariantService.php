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
    /**
     * Return all presets defined in 'Settings.Neos.Media.yaml' with presetName as key
     *
     * @param array $variantPresetConfigs
     * @param string $presetIdentifier
     * @return array
     */
    public function getAllPresetsByConfigs(array $variantPresetConfigs, string $presetIdentifier = ''): array
    {
        $presets = [];

        if (!empty($presetIdentifier) && !key_exists($presetIdentifier, $variantPresetConfigs)) {
            return $presets;
        }

        /** @var VariantPreset[] $variantPresetConfigs */
        if (!empty($presetIdentifier)) {
            foreach ($variantPresetConfigs[$presetIdentifier]->variants() as $presetVariant) {
                $presets[$presetIdentifier][] = $presetVariant->identifier();
            }
        } else {
            foreach ($variantPresetConfigs as $presetsConfig) {
                $variantPresetName = array_search($presetsConfig, $variantPresetConfigs);
                $presetKeys = array_keys($presetsConfig->variants());
                foreach ($presetKeys as $preset) {
                    $presets[(string)$variantPresetName][] = $preset;
                }
            }
        }

        return $presets;
    }
}
