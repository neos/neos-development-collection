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
 * Top URI path segment dimension preset detector
 */
final class UriPathSegmentDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    /**
     * @param Http\Uri $baseUri
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @param array $dimensionValues
     */
    public function processDimensionBaseUri(Http\Uri $baseUri, string $dimensionName, array $presetConfiguration, array $dimensionValues)
    {
        // no processing needs to be done here
    }
}
