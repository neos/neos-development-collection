<?php

namespace Neos\Neos\Tests\Unit\Http\ContentDimensionLinking\Fixtures;

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
use Neos\Neos\Http\ContentDimensionLinking\ContentDimensionPresetLinkProcessorInterface;

/**
 * Dummy dimension preset link processor implementing the required interface
 */
final class ValidDummyDimensionPresetLinkProcessor implements ContentDimensionPresetLinkProcessorInterface
{
    public function processDimensionBaseUri(Http\Uri $baseUri, string $dimensionName, array $presetConfiguration, array $preset, array $overrideOptions = null)
    {
    }
}
