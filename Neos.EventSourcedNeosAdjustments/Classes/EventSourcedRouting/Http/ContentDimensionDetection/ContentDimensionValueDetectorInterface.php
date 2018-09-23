<?php
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentDimensionDetection;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Dimension;
use Neos\Flow\Http;

/**
 * Interface to detect the current request's dimension value
 */
interface ContentDimensionValueDetectorInterface
{
    /**
     * @param Dimension\ContentDimension $contentDimension
     * @param Http\Component\ComponentContext $componentContext
     * @param array $overrideOptions
     * @return Dimension\ContentDimensionValue|null
     */
    public function detectValue(Dimension\ContentDimension $contentDimension, Http\Component\ComponentContext $componentContext, array $overrideOptions = null): ?Dimension\ContentDimensionValue;
}
