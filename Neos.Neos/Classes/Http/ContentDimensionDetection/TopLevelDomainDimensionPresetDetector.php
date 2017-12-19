<?php
namespace Neos\Neos\Http\ContentDimensionDetection;

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
final class TopLevelDomainDimensionPresetDetector implements ContentDimensionPresetDetectorInterface
{
    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * @param string $dimensionName
     * @param array $presets
     * @param Http\Component\ComponentContext $componentContext
     * @param array|null $overrideOptions
     * @return array|null
     */
    public function detectPreset(string $dimensionName, array $presets, Http\Component\ComponentContext $componentContext, array $overrideOptions = null)
    {
        $host = $componentContext->getHttpRequest()->getUri()->getHost();
        $hostLength = mb_strlen($host);
        foreach ($presets as $preset) {
            $pivot = $hostLength - mb_strlen($preset['resolutionValue']);

            if (mb_substr($host, $pivot) === $preset['resolutionValue']) {
                return $preset;
            }
        }

        return null;
    }
}
