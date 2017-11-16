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

/**
 * Interface to detect the current request's dimension preset
 */
interface ContentDimensionPresetDetectorInterface
{
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
     * @param string $dimensionName
     * @param array $presets
     * @param Http\Component\ComponentContext $componentContext
     * @param array $overrideOptions
     * @return array|null
     */
    public function detectPreset(string $dimensionName, array $presets, Http\Component\ComponentContext $componentContext, array $overrideOptions = null);
}
