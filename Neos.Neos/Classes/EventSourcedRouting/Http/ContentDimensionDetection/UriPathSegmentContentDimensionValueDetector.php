<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionDetection;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\Utility\Arrays;
use Psr\Http\Message\ServerRequestInterface;

/**
 * URI path segment based dimension value detector
 */
final class UriPathSegmentContentDimensionValueDetector implements ContentDimensionValueDetectorInterface
{
    /**
     * @var array<string,mixed>
     */
    protected array $defaultOptions = [
        'delimiter' => '_',
        'offset' => 0
    ];

    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function detectValue(
        Dimension\ContentDimension $contentDimension,
        ServerRequestInterface $request,
        ?array $overrideOptions = null
    ): ?Dimension\ContentDimensionValue {
        $options = $overrideOptions
            ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions)
            : $this->defaultOptions;
        $requestPath = $request->getUri()->getPath();

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

            foreach ($contentDimension->values as $contentDimensionValue) {
                $resolutionValue = $contentDimensionValue->getConfigurationValue('resolution.value');
                if ($resolutionValue === $detectedValue) {
                    return $contentDimensionValue;
                }
            }
        }

        return null;
    }
}
