<?php

namespace Neos\Neos\Http\ContentDimensionLinking;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;

/**
 * Subdomain based dimension preset detector
 */
final class SubdomainDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    /**
     * @param Http\Uri $baseUri
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @param array $preset
     */
    public function processDimensionBaseUri(Http\Uri $baseUri, string $dimensionName, array $presetConfiguration, array $preset)
    {
        $currentValue = null;
        foreach ($presetConfiguration['presets'] as $availablePreset) {
            if (empty($availablePreset['resolutionValue'])) {
                $currentValue = $availablePreset['resolutionValue'];
            } elseif (mb_substr($baseUri->getHost(), 0, mb_strlen($availablePreset['resolutionValue'] . '.')) === $availablePreset['resolutionValue'] . '.') {
                $currentValue = $availablePreset['resolutionValue'];
                break;
            }
        }

        $newValue = $preset['resolutionValue'];

        $pivot = mb_strlen($currentValue);
        if (empty($currentValue) && !empty($newValue)) {
            $newValue .= '.';
        } elseif (!empty($currentValue) && empty($newValue)) {
            $pivot++;
        }

        $baseUri->setHost($newValue . mb_substr($baseUri->getHost(), $pivot));
    }
}
