<?php

namespace Neos\Neos\Tests\Unit\Http\ContentDimensionDetection\Fixtures;

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
use Neos\Neos\Http\ContentDimensionDetection\ContentDimensionPresetDetectorInterface;

/**
 * Dummy dimension preset detector implementing the required interface
 */
final class ValidDummyDimensionPresetDetector implements ContentDimensionPresetDetectorInterface
{
    /**
     * @param string $dimensionName
     * @param array $presets
     * @param Http\Component\ComponentContext $componentContext
     * @param array|null $overrideOptions
     * @return array|null
     */
    public function detectPreset(string $dimensionName, array $presets, Http\Component\ComponentContext $componentContext, array $overrideOptions = null)
    {
        return null;
    }
}
