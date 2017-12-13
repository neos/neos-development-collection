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
use Neos\Flow\Mvc\Routing;
use Neos\Utility\Arrays;

/**
 * URI path segment dimension preset link processor
 */
final class UriPathSegmentDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'offset' => 0,
        'delimiter' => '_'
    ];

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
        $options = $overrideOptions ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions) : $this->defaultOptions;
        if ($options['offset'] > 0) {
            $pathSegmentPart = $options['delimiter'];
        } else {
            $pathSegmentPart = '';
        }
        $pathSegmentPart .= $preset['resolutionValue'];
        $baseUri->setPath('/' . ltrim($baseUri->getPath() . $pathSegmentPart, '/'));
    }

    /**
     * @param Routing\Dto\UriConstraints $uriConstraints
     * @param string $dimensionName
     * @param array $presetConfiguration
     * @param array $preset
     * @param array|null $overrideOptions
     * @return Routing\Dto\UriConstraints
     */
    public function processUriConstraints(
        Routing\Dto\UriConstraints $uriConstraints,
        string $dimensionName,
        array $presetConfiguration,
        array $preset,
        array $overrideOptions = null
    ): Routing\Dto\UriConstraints {
        $options = $overrideOptions ? Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $overrideOptions) : $this->defaultOptions;
        $pathSegmentPart = $options['offset'] > 0 ? $options['delimiter'] : '';
        $pathSegmentPart .= $preset['resolutionValue'];

        return $uriConstraints->withPathPrefix($pathSegmentPart, true);
    }
}
