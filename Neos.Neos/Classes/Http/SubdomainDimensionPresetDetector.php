<?php

namespace Neos\Neos\Http;

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
class SubdomainDimensionPresetDetector implements ContentDimensionPresetDetectorInterface
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
        foreach ($presets as $preset) {
            $valueLength = mb_strlen($preset['detectionValue']);

            if (mb_substr($host, 0, $valueLength) === $preset['detectionValue']) {
                return $preset;
            }
        }

        return null;
    }
}
