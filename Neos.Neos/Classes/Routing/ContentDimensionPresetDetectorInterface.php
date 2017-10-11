<?php

namespace Neos\Neos\Routing;

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
 * Interface to detect the current request's dimension values
 */
interface ContentDimensionPresetDetectorInterface
{
    const DETECTION_MODE_SUBDOMAIN = 'subdomain';
    const DETECTION_MODE_DOMAINNAME = 'domainName';
    const DETECTION_MODE_TOPLEVELDOMAIN = 'topLevelDomain';
    const DETECTION_MODE_URIPATHSEGMENT = 'uriPathSegment';


    /**
     * Detects the content dimensions in the given URI as defined in presets
     *
     * Returns an array of dimension values like:
     *
     * [
     *      language => [
     *          0 => en_US
     *      ]
     * ]
     *
     * @param Http\Uri $uri
     * @param string &$requestPath
     * @param bool $allowEmptyValues
     * @return array
     * @todo check whether we need allowEmptyValues or if it is enough to check existing defaults on dimension level
     */
    public function extractDimensionValues(Http\Uri $uri, string &$requestPath, bool $allowEmptyValues = false): array;
}
