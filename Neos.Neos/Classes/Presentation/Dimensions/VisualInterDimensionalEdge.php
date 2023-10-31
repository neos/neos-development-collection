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

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class VisualInterDimensionalEdge
{
    private const VERTICAL_OFFSET = 40;

    public function __construct(
        public readonly int $x1,
        public readonly int $y1,
        public readonly int $x2,
        public readonly int $y2,
        public readonly string $color,
        public readonly float $opacity,
        public readonly ?string $from,
        public readonly ?string $style,
        public readonly ?string $to
    ) {
    }

    public static function forVisualDimensionSpacePoints(
        VisualWeightedDimensionSpacePoint $startPoint,
        VisualWeightedDimensionSpacePoint $endPoint,
        string $color,
        float $opacity,
        bool $isInactive
    ): self {
        return new self(
            $startPoint->x,
            $startPoint->y - self::VERTICAL_OFFSET,
            $endPoint->x,
            $endPoint->y + self::VERTICAL_OFFSET,
            $color,
            $opacity,
            $isInactive ? $startPoint->id : null,
            $isInactive ? 'display: none' : null,
            $isInactive ? $endPoint->id : null
        );
    }
}
