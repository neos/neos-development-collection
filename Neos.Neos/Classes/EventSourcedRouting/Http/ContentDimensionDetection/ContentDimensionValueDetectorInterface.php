<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\EventSourcedRouting\Http\ContentDimensionDetection;

use Neos\ContentRepository\DimensionSpace\Dimension;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface to detect the current request's dimension value
 */
interface ContentDimensionValueDetectorInterface
{
    /**
     * @param array<string,mixed>|null $overrideOptions
     */
    public function detectValue(
        Dimension\ContentDimension $contentDimension,
        ServerRequestInterface $request,
        ?array $overrideOptions = null
    ): ?Dimension\ContentDimensionValue;
}
