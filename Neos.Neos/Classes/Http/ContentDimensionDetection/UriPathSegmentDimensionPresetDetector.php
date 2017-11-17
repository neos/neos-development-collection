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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;
use Neos\Utility\Arrays;

/**
 * URI path segment based dimension preset detector
 */
final class UriPathSegmentDimensionPresetDetector implements ContentDimensionPresetDetectorInterface
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'delimiter' => '_',
        'offset' => 0
    ];


    /**
     * @param string $dimensionName
     * @param array $presets
     * @param Http\Component\ComponentContext $componentContext
     * @param array|null $overrideOptions
     * @return array|null
     */
    public function detectPreset(string $dimensionName, array $presets, Http\Component\ComponentContext $componentContext, array $overrideOptions = null)
    {
        $options = $overrideOptions ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions) : $this->defaultOptions;
        $requestPath = $componentContext->getHttpRequest()->getUri()->getPath();

        if (!empty($requestPath) && $requestPath !== '/' && mb_strpos($requestPath, '/') !== false) {
            $pathSegments = explode('/', ($requestPath));
            $detectedValues = explode($options['delimiter'], $pathSegments[1]);
            if (!isset($detectedValues[$options['offset']])) {
                return null;
            }
            $detectedValue = $detectedValues[$options['offset']];
            $pivot = mb_strpos($detectedValue, '@');
            if ($pivot !== false) {
                $detectedValue = mb_substr($detectedValue, 0, $pivot);
            }

            foreach ($presets as $preset) {
                $resolutionValue = $preset['resolutionValue'] ?? $preset['uriSegment'];
                if ($resolutionValue === $detectedValue) {
                    return $preset;
                }
            }
        }

        return null;
    }
}
