<?php
namespace Neos\Neos\Tests\Unit\Routing\Fixtures;

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
use Neos\Neos\Http\ContentDimensionLinking\UriPathSegmentDimensionPresetLinkProcessor;

/**
 * Dummy dimension preset link processor not implementing the required interface
 */
final class UriSegmentDimensionPresetLinkProcessorResolver
{
    public function resolveDimensionPresetLinkProcessor()
    {
        return new UriPathSegmentDimensionPresetLinkProcessor();
    }
}
