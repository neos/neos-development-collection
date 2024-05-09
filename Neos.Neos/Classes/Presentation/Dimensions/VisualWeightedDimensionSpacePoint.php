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

use Neos\ContentRepository\Core\Dimension\ContentDimensionValueSpecializationDepth;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\WeightedDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class VisualWeightedDimensionSpacePoint
{
    public int $textX;

    public int $textY;

    public function __construct(
        public string $id,
        public string $name,
        public int $x,
        public int $y,
        public string $color
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
        /** @var ContentDimensionValueSpecializationDepth $weight */
        foreach ($dimensionSpacePoint->weight->value as $weight) {
            $depth += $weight->value;
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
