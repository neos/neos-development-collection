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

namespace Neos\Neos\Presentation\Dimensions;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The IntraDimensionalFallbackGraph presentation model for SVG
 */
#[Flow\Proxy(false)]
final class VisualIntraDimensionalVariationGraph
{
    private function __construct(
        /** @var array<string,VisualContentDimension> $dimensions */
        public readonly array $dimensions,
        public readonly int $width,
        public readonly int $height
    ) {
    }

    public static function fromContentDimensionSource(ContentDimensionSourceInterface $contentDimensionSource): self
    {
        $dimensions = [];
        $horizontalOffset = 0;
        $counter = 0;
        $width = 0;
        $height = 0;

        foreach ($contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
            $dimensions[(string)$contentDimension->id] = VisualContentDimension::fromContentDimension(
                $contentDimension,
                $horizontalOffset,
                $counter,
                $width,
                $height
            );
            $horizontalOffset += 30;
        }

        return new self(
            $dimensions,
            $width,
            $height
        );
    }
}
