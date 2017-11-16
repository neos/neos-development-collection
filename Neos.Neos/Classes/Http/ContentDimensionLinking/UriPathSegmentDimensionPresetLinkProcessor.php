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
 * URI path segment dimension preset link processor
 */
final class UriPathSegmentDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    /**
     * @param Http\Uri $baseUri
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @param array $preset
     */
    public function processDimensionBaseUri(Http\Uri $baseUri, string $dimensionName, array $presetConfiguration, array $preset)
    {
        if ($presetConfiguration[$dimensionName]['resolution']['options']['offset'] > 0) {
            $pathSegmentPart = $presetConfiguration[$dimensionName]['resolution']['options']['delimiter'];
        } else {
            $pathSegmentPart = '/';
        }
        $pathSegmentPart .= $preset['resolutionValue'];
        $baseUri->setPath($baseUri->getPath() . $pathSegmentPart);
    }
}
