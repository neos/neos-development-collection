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

use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\WeightedDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class VisualWeightedDimensionSpacePoint
{
    public readonly int $textX;

    public readonly int $textY;

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $x,
        public readonly int $y,
        public readonly string $color
    ) {
        $this->textX = $x - 40;
        $this->textY = $y - 5 + 50; // 50 for padding
    }

    public static function fromDimensionSpacePoint(
        WeightedDimensionSpacePoint $dimensionSpacePoint,
        ?DimensionSpacePoint $referenceDimensionSpacePoint,
        int &$horizontalOffset,
        int &$y,
        int &$width,
        int &$height
    ): self {
        $depth = 0;
        foreach ($dimensionSpacePoint->weight->weight as $weight) {
            $depth += $weight->depth;
        }
        $previousY = $y;
        $y = $depth * 110 + 42;
        if ($y <= $previousY) {
            $horizontalOffset += 110;
        }
        $x = $horizontalOffset + 42;

        $width = max($width, $x + 42 + 10);
        $height = max($height, $y + 42 + 10);

        return new self(
            $dimensionSpacePoint->getIdentityHash(),
            implode(', ', $dimensionSpacePoint->dimensionSpacePoint->coordinates),
            $x,
            $y,
            $referenceDimensionSpacePoint
                && $dimensionSpacePoint->dimensionSpacePoint->equals($referenceDimensionSpacePoint)
                ? '#00B5FF'
                : '#3F3F3F',
        );
    }
}
