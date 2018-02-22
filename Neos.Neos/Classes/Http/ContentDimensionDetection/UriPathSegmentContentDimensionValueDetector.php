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

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Http;
use Neos\Utility\Arrays;

/**
 * URI path segment based dimension value detector
 */
final class UriPathSegmentContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'delimiter' => '_',
        'offset' => 0
    ];

    /**
     * @param Dimension\ContentDimension $contentDimension
     * @param Http\Component\ComponentContext $componentContext
     * @param array|null $overrideOptions
     * @return Dimension\ContentDimensionValue|null
     */
    public function detectValue(Dimension\ContentDimension $contentDimension, Http\Component\ComponentContext $componentContext, array $overrideOptions = null): ?Dimension\ContentDimensionValue
    {
        $options = $overrideOptions ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions) : $this->defaultOptions;
        $requestPath = $componentContext->getHttpRequest()->getUri()->getPath();

        if (!empty($requestPath) && $requestPath !== '/' && mb_strpos($requestPath, '/') !== false) {
            $pivot = mb_strpos($requestPath, '@');
            if ($pivot !== false) {
                $requestPath = mb_substr($requestPath, 0, $pivot);
            }

            $pathSegments = explode('/', ($requestPath));
            $detectedValues = explode($options['delimiter'], $pathSegments[1]);
            if (!isset($detectedValues[$options['offset']])) {
                return null;
            }
            $detectedValue = $detectedValues[$options['offset']];

            foreach ($contentDimension->getValues() as $contentDimensionValue) {
                $resolutionValue = $contentDimensionValue->getConfigurationValue('resolution.value');
                if ($resolutionValue === $detectedValue) {
                    return $contentDimensionValue;
                }
            }
        }

        return null;
    }
}
