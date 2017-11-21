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

use Neos\Flow\Http;

/**
 * Top level domain based dimension preset detector
 */
final class TopLevelDomainDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    /**
     * @param Http\Uri $baseUri
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @param array $preset
     * @param array|null $overrideOptions
     * @return void
     */
    public function processDimensionBaseUri(Http\Uri $baseUri, string $dimensionName, array $presetConfiguration, array $preset, array $overrideOptions = null)
    {
        $currentValue = null;
        foreach ($presetConfiguration['presets'] as $availablePreset) {
            if (mb_substr($baseUri->getHost(), -mb_strlen($availablePreset['resolutionValue'])) === $availablePreset['resolutionValue']) {
                $currentValue = $availablePreset['resolutionValue'];
                break;
            }
        }

        $newValue = $preset['resolutionValue'];

        if ($newValue !== $currentValue) {
            $baseUri->setHost(mb_substr($baseUri->getHost(), 0, -mb_strlen($currentValue)) . $newValue);
        }
    }
}
